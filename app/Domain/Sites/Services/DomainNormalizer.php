<?php

namespace App\Domain\Sites\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DomainNormalizer
{
    public function normalize(string $input): string
    {
        Log::debug('[site.domain.normalize] started', ['input' => $input]);

        $raw = trim(Str::of($input)->lower()->toString());

        if ($raw === '') {
            throw new InvalidArgumentException('Domain cannot be empty.');
        }

        $candidate = $raw;

        if (str_contains($candidate, '://')) {
            $candidate = (string) parse_url($candidate, PHP_URL_HOST);
        } else {
            $candidate = (string) parse_url('https://'.$candidate, PHP_URL_HOST);
        }

        $candidate = trim($candidate, " \t\n\r\0\x0B.");

        if ($candidate === '') {
            throw new InvalidArgumentException('Invalid domain value.');
        }

        $normalized = function_exists('idn_to_ascii')
            ? idn_to_ascii($candidate, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)
            : $candidate;

        if (! is_string($normalized) || $normalized === '') {
            throw new InvalidArgumentException('Unable to normalize domain.');
        }

        if ($this->isAllowedLoopbackDomain($normalized)) {
            Log::debug('[site.domain.normalize] completed', ['normalized_domain' => $normalized]);

            return $normalized;
        }

        if (! preg_match('/^(?=.{1,255}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$/', $normalized)) {
            throw new InvalidArgumentException('Invalid domain format.');
        }

        Log::debug('[site.domain.normalize] completed', ['normalized_domain' => $normalized]);

        return $normalized;
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
}
