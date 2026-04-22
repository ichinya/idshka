<?php

namespace App\Domain\Sites\Services;

use App\Domain\Sites\Contracts\DnsTxtRecordLookup;
use Illuminate\Support\Facades\Log;

final class NativeDnsTxtRecordLookup implements DnsTxtRecordLookup
{
    /**
     * @return array<int, string>
     */
    public function getTxtRecords(string $host): array
    {
        Log::debug('[site.verify.dns_lookup] started', ['host' => $host]);

        if (! function_exists('dns_get_record')) {
            Log::warning('[site.verify.dns_lookup] dns_get_record unavailable', ['host' => $host]);

            return [];
        }

        $records = @dns_get_record($host, DNS_TXT);

        if (! is_array($records)) {
            Log::warning('[site.verify.dns_lookup] dns query failed', ['host' => $host]);

            return [];
        }

        $txtValues = [];

        foreach ($records as $record) {
            if (is_array($record) && isset($record['txt']) && is_string($record['txt'])) {
                $txtValues[] = $record['txt'];
            }
        }

        Log::debug('[site.verify.dns_lookup] completed', [
            'host' => $host,
            'records_count' => count($txtValues),
        ]);

        return $txtValues;
    }
}
