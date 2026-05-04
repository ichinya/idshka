<?php

namespace App\Domain\Sites\Services;

use App\Domain\Sites\Models\Site;
use App\Support\SafeLogContext;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;

final class WellKnownFileVerificationChecker
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function check(Site $site, string $token): VerificationCheckResult
    {
        if (! $this->hostResolvesToPublicAddress($site->normalized_domain)) {
            Log::warning('[FIX:security] blocked_file_verification_to_non_public_host', SafeLogContext::from([
                'site_id' => $site->id,
                'host' => $site->normalized_domain,
            ]));

            return VerificationCheckResult::failed('file_host_not_public');
        }

        $url = sprintf('https://%s/.well-known/idshka-site-verification.txt', $site->normalized_domain);

        Log::info('[site.verify.file] started', SafeLogContext::from([
            'site_id' => $site->id,
            'url' => $url,
        ]));

        try {
            $response = $this->http
                ->timeout(5)
                ->accept('text/plain')
                ->withoutRedirecting()
                ->get($url);
        } catch (\Throwable $exception) {
            Log::warning('[site.verify.file] request failed', SafeLogContext::from([
                'site_id' => $site->id,
                'exception_class' => $exception::class,
            ]));

            return VerificationCheckResult::failed('file_request_failed');
        }

        if (! $response->ok()) {
            Log::warning('[site.verify.file] non_ok_response', SafeLogContext::from([
                'site_id' => $site->id,
                'status' => $response->status(),
            ]));

            return VerificationCheckResult::failed('file_http_status_invalid');
        }

        $effectiveUri = $response->effectiveUri();

        if ($effectiveUri !== null && strtolower((string) $effectiveUri->getHost()) !== $site->normalized_domain) {
            Log::warning('[site.verify.file] redirected_to_untrusted_host', SafeLogContext::from([
                'site_id' => $site->id,
                'effective_host' => strtolower((string) $effectiveUri->getHost()),
            ]));

            return VerificationCheckResult::failed('file_redirect_untrusted_host');
        }

        $body = trim($response->body());

        if ($body !== $token) {
            Log::warning('[site.verify.file] token mismatch', SafeLogContext::from([
                'site_id' => $site->id,
                'body_length' => strlen($body),
            ]));

            return VerificationCheckResult::failed('file_token_mismatch');
        }

        Log::info('[site.verify.file] verification passed', SafeLogContext::from([
            'site_id' => $site->id,
        ]));

        return VerificationCheckResult::passed();
    }

    private function hostResolvesToPublicAddress(string $host): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $ips = [];

        if (function_exists('dns_get_record')) {
            $records = dns_get_record($host, DNS_A + DNS_AAAA);

            if (is_array($records)) {
                foreach ($records as $record) {
                    if (is_array($record) && isset($record['ip']) && is_string($record['ip'])) {
                        $ips[] = $record['ip'];
                    }

                    if (is_array($record) && isset($record['ipv6']) && is_string($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        if ($ips === []) {
            $fallback = gethostbynamel($host);
            if (is_array($fallback)) {
                $ips = array_merge($ips, $fallback);
            }
        }

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            $isPublicIp = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;

            if (! $isPublicIp) {
                return false;
            }
        }

        return true;
    }
}
