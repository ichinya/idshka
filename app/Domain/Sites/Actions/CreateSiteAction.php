<?php

namespace App\Domain\Sites\Actions;

use App\Domain\Sites\Enums\SiteVerificationMethod;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Events\SiteConnected;
use App\Domain\Sites\Exceptions\SiteDomainConflictException;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteVerification;
use App\Domain\Sites\Services\DomainNormalizer;
use App\Domain\Sites\Services\SiteIdFactory;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateSiteAction
{
    public function __construct(
        private readonly DomainNormalizer $domainNormalizer,
        private readonly SiteIdFactory $siteIdFactory,
    ) {}

    public function handle(int $ownerUserId, string $domain, ?string $displayName): Site
    {
        Log::info('[site.create] started', [
            'owner_user_id' => $ownerUserId,
            'domain' => $domain,
        ]);

        $normalizedDomain = $this->domainNormalizer->normalize($domain);

        $existingOwned = Site::query()
            ->where('owner_user_id', $ownerUserId)
            ->where('normalized_domain', $normalizedDomain)
            ->first();

        if ($existingOwned !== null) {
            Log::warning('[site.create] duplicate_domain_for_owner', [
                'owner_user_id' => $ownerUserId,
                'normalized_domain' => $normalizedDomain,
                'site_id' => $existingOwned->id,
            ]);

            throw new SiteDomainConflictException('site_for_domain_already_exists');
        }

        $takenByVerified = Site::query()
            ->where('normalized_domain', $normalizedDomain)
            ->where('verification_status', SiteVerificationStatus::Verified->value)
            ->where('owner_user_id', '!=', $ownerUserId)
            ->exists();

        if ($takenByVerified) {
            Log::warning('[site.create] verified_domain_owned_by_other_user', [
                'owner_user_id' => $ownerUserId,
                'normalized_domain' => $normalizedDomain,
            ]);

            throw new SiteDomainConflictException('verified_domain_owned_by_another_user');
        }

        $expiresAt = CarbonImmutable::now()->addMinutes(30);
        $token = Str::random(48);

        try {
            $site = DB::transaction(function () use ($ownerUserId, $displayName, $domain, $normalizedDomain, $expiresAt, $token): Site {
                $site = Site::query()->create([
                    'id' => $this->siteIdFactory->make(),
                    'owner_user_id' => $ownerUserId,
                    'display_name' => $displayName,
                    'domain' => $domain,
                    'normalized_domain' => $normalizedDomain,
                    'verification_status' => SiteVerificationStatus::Pending->value,
                ]);

                SiteVerification::query()->create([
                    'site_id' => $site->id,
                    'method' => SiteVerificationMethod::DnsTxt->value,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'status' => SiteVerificationStatus::Pending->value,
                ]);

                SiteVerification::query()->create([
                    'site_id' => $site->id,
                    'method' => SiteVerificationMethod::File->value,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'status' => SiteVerificationStatus::Pending->value,
                ]);

                return $site->refresh();
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! $this->isOwnerDomainUniqueViolation($exception)) {
                throw $exception;
            }

            $existingOwned = Site::query()
                ->where('owner_user_id', $ownerUserId)
                ->where('normalized_domain', $normalizedDomain)
                ->first();

            Log::warning('[site.create] duplicate_domain_for_owner_after_unique_race', [
                'owner_user_id' => $ownerUserId,
                'normalized_domain' => $normalizedDomain,
                'site_id' => $existingOwned?->id,
            ]);

            throw new SiteDomainConflictException('site_for_domain_already_exists', 0, $exception);
        }

        SiteConnected::dispatch($site);

        Log::info('[site.create] completed', [
            'owner_user_id' => $ownerUserId,
            'site_id' => $site->id,
            'normalized_domain' => $site->normalized_domain,
        ]);

        return $site;
    }

    private function isOwnerDomainUniqueViolation(UniqueConstraintViolationException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'sites_owner_domain_unique')
            || str_contains($message, 'UNIQUE constraint failed: sites.owner_user_id, sites.normalized_domain');
    }
}
