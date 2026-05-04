<?php

namespace App\Domain\Sites\Actions;

use App\Domain\Sites\Enums\SiteVerificationMethod;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Events\SiteVerificationCompleted;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteVerification;
use App\Domain\Sites\Services\DnsTxtVerificationChecker;
use App\Domain\Sites\Services\VerificationCheckResult;
use App\Domain\Sites\Services\WellKnownFileVerificationChecker;
use App\Support\SafeLogContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class VerifySiteDomainAction
{
    public function __construct(
        private readonly DnsTxtVerificationChecker $dnsChecker,
        private readonly WellKnownFileVerificationChecker $fileChecker,
    ) {}

    public function handle(Site $site, SiteVerificationMethod $method): SiteVerification
    {
        Log::info('[site.verify] started', SafeLogContext::from([
            'site_id' => $site->id,
            'method' => $method->value,
        ]));

        /** @var SiteVerification $verification */
        $verification = SiteVerification::query()
            ->where('site_id', $site->id)
            ->where('method', $method->value)
            ->latest('id')
            ->firstOrFail();

        if ($verification->status === SiteVerificationStatus::Verified->value) {
            Log::info('[site.verify] already_verified', SafeLogContext::from([
                'site_id' => $site->id,
                'verification_id' => $verification->id,
            ]));

            return $verification;
        }

        if (CarbonImmutable::now()->greaterThan($verification->expires_at)) {
            DB::transaction(function () use ($site): void {
                SiteVerification::query()
                    ->where('site_id', $site->id)
                    ->whereNull('verified_at')
                    ->where('expires_at', '<', CarbonImmutable::now())
                    ->update([
                        'status' => SiteVerificationStatus::Expired->value,
                        'last_error' => 'verification_expired',
                    ]);

                $this->issueFreshChallenges($site);
            });

            SiteVerificationCompleted::dispatch($site, $method, false, 'verification_expired');

            return $verification->refresh();
        }

        $result = $this->runChecker($method, $site, $verification->token);
        $finalResult = $result;

        DB::transaction(function () use ($site, $verification, &$finalResult): void {
            if ($finalResult->passed) {
                $domainIsTakenByAnotherVerifiedOwner = Site::query()
                    ->where('normalized_domain', $site->normalized_domain)
                    ->lockForUpdate()
                    ->where('owner_user_id', '!=', $site->owner_user_id)
                    ->where('verification_status', SiteVerificationStatus::Verified->value)
                    ->exists();

                if ($domainIsTakenByAnotherVerifiedOwner) {
                    Log::warning('[FIX:security] blocked_conflicting_verified_domain_claim', SafeLogContext::from([
                        'site_id' => $site->id,
                        'normalized_domain' => $site->normalized_domain,
                    ]));

                    $verification->update([
                        'status' => SiteVerificationStatus::Failed->value,
                        'last_error' => 'verified_domain_owned_by_another_user',
                    ]);

                    $finalResult = VerificationCheckResult::failed('verified_domain_owned_by_another_user');

                    return;
                }

                $verification->update([
                    'status' => SiteVerificationStatus::Verified->value,
                    'verified_at' => now(),
                    'last_error' => null,
                ]);

                $site->update([
                    'verification_status' => SiteVerificationStatus::Verified->value,
                    'verified_at' => now(),
                ]);

                return;
            }

            $verification->update([
                'status' => SiteVerificationStatus::Failed->value,
                'last_error' => $finalResult->errorCode,
            ]);
        });

        SiteVerificationCompleted::dispatch($site->refresh(), $method, $finalResult->passed, $finalResult->errorCode);

        Log::info('[site.verify] completed', SafeLogContext::from([
            'site_id' => $site->id,
            'method' => $method->value,
            'success' => $finalResult->passed,
            'error_code' => $finalResult->errorCode,
        ]));

        return $verification->refresh();
    }

    private function runChecker(SiteVerificationMethod $method, Site $site, string $token): VerificationCheckResult
    {
        if ($this->isAllowedLoopbackDomain($site->normalized_domain)) {
            Log::info('[site.verify] local_loopback_domain_verified', SafeLogContext::from([
                'site_id' => $site->id,
                'normalized_domain' => $site->normalized_domain,
                'method' => $method->value,
            ]));

            return VerificationCheckResult::passed();
        }

        return match ($method) {
            SiteVerificationMethod::DnsTxt => $this->dnsChecker->check($site, $token),
            SiteVerificationMethod::File => $this->fileChecker->check($site, $token),
        };
    }

    private function isAllowedLoopbackDomain(string $domain): bool
    {
        if (! (bool) config('sites.allow_loopback_domains', false)) {
            return false;
        }

        if ($domain === 'localhost') {
            return true;
        }

        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return str_starts_with($domain, '127.');
        }

        return $domain === '::1';
    }

    private function issueFreshChallenges(Site $site): void
    {
        $expiresAt = CarbonImmutable::now()->addMinutes(30);
        $token = Str::random(48);
        $verificationIds = [];

        foreach (SiteVerificationMethod::cases() as $method) {
            $verification = SiteVerification::query()->create([
                'site_id' => $site->id,
                'method' => $method->value,
                'token' => $token,
                'expires_at' => $expiresAt,
                'status' => SiteVerificationStatus::Pending->value,
            ]);

            $verificationIds[] = $verification->id;
        }

        Log::info('[FIX:site-verification-refresh] issued fresh verification challenges after expiry', SafeLogContext::from([
            'site_id' => $site->id,
            'verification_ids' => $verificationIds,
            'expires_at' => $expiresAt->toISOString(),
        ]));
    }
}
