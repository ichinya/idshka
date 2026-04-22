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
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class VerifySiteDomainAction
{
    public function __construct(
        private readonly DnsTxtVerificationChecker $dnsChecker,
        private readonly WellKnownFileVerificationChecker $fileChecker,
    ) {
    }

    public function handle(Site $site, SiteVerificationMethod $method): SiteVerification
    {
        Log::info('[site.verify] started', [
            'site_id' => $site->id,
            'method' => $method->value,
        ]);

        /** @var SiteVerification $verification */
        $verification = SiteVerification::query()
            ->where('site_id', $site->id)
            ->where('method', $method->value)
            ->latest('id')
            ->firstOrFail();

        if ($verification->status === SiteVerificationStatus::Verified->value) {
            Log::info('[site.verify] already_verified', [
                'site_id' => $site->id,
                'verification_id' => $verification->id,
            ]);

            return $verification;
        }

        if (CarbonImmutable::now()->greaterThan($verification->expires_at)) {
            $verification->update([
                'status' => SiteVerificationStatus::Expired->value,
                'last_error' => 'verification_expired',
            ]);

            SiteVerificationCompleted::dispatch($site, $method, false, 'verification_expired');

            return $verification->refresh();
        }

        $result = $this->runChecker($method, $site, $verification->token);

        DB::transaction(function () use ($site, $verification, $result): void {
            if ($result->passed) {
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
                'last_error' => $result->errorCode,
            ]);
        });

        SiteVerificationCompleted::dispatch($site->refresh(), $method, $result->passed, $result->errorCode);

        Log::info('[site.verify] completed', [
            'site_id' => $site->id,
            'method' => $method->value,
            'success' => $result->passed,
            'error_code' => $result->errorCode,
        ]);

        return $verification->refresh();
    }

    private function runChecker(SiteVerificationMethod $method, Site $site, string $token): VerificationCheckResult
    {
        return match ($method) {
            SiteVerificationMethod::DnsTxt => $this->dnsChecker->check($site, $token),
            SiteVerificationMethod::File => $this->fileChecker->check($site, $token),
        };
    }
}
