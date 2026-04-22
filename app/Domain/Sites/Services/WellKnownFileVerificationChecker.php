<?php

namespace App\Domain\Sites\Services;

use App\Domain\Sites\Models\Site;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;

final class WellKnownFileVerificationChecker
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function check(Site $site, string $token): VerificationCheckResult
    {
        $url = sprintf('https://%s/.well-known/idshka-site-verification.txt', $site->normalized_domain);

        Log::info('[site.verify.file] started', [
            'site_id' => $site->id,
            'url' => $url,
        ]);

        try {
            $response = $this->http
                ->timeout(5)
                ->accept('text/plain')
                ->get($url);
        } catch (\Throwable $exception) {
            Log::warning('[site.verify.file] request failed', [
                'site_id' => $site->id,
                'exception_class' => $exception::class,
            ]);

            return VerificationCheckResult::failed('file_request_failed');
        }

        if (! $response->ok()) {
            Log::warning('[site.verify.file] non_ok_response', [
                'site_id' => $site->id,
                'status' => $response->status(),
            ]);

            return VerificationCheckResult::failed('file_http_status_invalid');
        }

        $effectiveUri = $response->effectiveUri();

        if ($effectiveUri !== null && strtolower((string) $effectiveUri->getHost()) !== $site->normalized_domain) {
            Log::warning('[site.verify.file] redirected_to_untrusted_host', [
                'site_id' => $site->id,
                'effective_host' => strtolower((string) $effectiveUri->getHost()),
            ]);

            return VerificationCheckResult::failed('file_redirect_untrusted_host');
        }

        $body = trim($response->body());

        if ($body !== $token) {
            Log::warning('[site.verify.file] token mismatch', [
                'site_id' => $site->id,
                'body_length' => strlen($body),
            ]);

            return VerificationCheckResult::failed('file_token_mismatch');
        }

        Log::info('[site.verify.file] verification passed', [
            'site_id' => $site->id,
        ]);

        return VerificationCheckResult::passed();
    }
}
