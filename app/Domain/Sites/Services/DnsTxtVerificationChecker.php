<?php

namespace App\Domain\Sites\Services;

use App\Domain\Sites\Contracts\DnsTxtRecordLookup;
use App\Domain\Sites\Models\Site;
use App\Support\SafeLogContext;
use Illuminate\Support\Facades\Log;

final class DnsTxtVerificationChecker
{
    public function __construct(
        private readonly DnsTxtRecordLookup $dnsLookup,
    ) {}

    public function check(Site $site, string $token): VerificationCheckResult
    {
        $host = '_idshka.'.$site->normalized_domain;
        $expectedValue = 'idshka-site-verification='.$token;

        Log::info('[site.verify.dns] started', SafeLogContext::from([
            'site_id' => $site->id,
            'host' => $host,
        ]));

        $records = $this->dnsLookup->getTxtRecords($host);

        $matched = collect($records)->contains(fn (string $value): bool => trim($value) === $expectedValue);

        if (! $matched) {
            Log::warning('[site.verify.dns] verification failed', SafeLogContext::from([
                'site_id' => $site->id,
                'host' => $host,
                'records_count' => count($records),
            ]));

            return VerificationCheckResult::failed('dns_txt_mismatch');
        }

        Log::info('[site.verify.dns] verification passed', SafeLogContext::from([
            'site_id' => $site->id,
            'host' => $host,
        ]));

        return VerificationCheckResult::passed();
    }
}
