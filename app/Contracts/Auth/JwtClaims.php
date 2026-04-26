<?php

namespace App\Contracts\Auth;

use InvalidArgumentException;

final readonly class JwtClaims
{
    public const TOKEN_TYPE_USER_API = 'user_api';

    public const TOKEN_TYPE_ID_TOKEN = 'id_token';

    public const TOKEN_TYPE_WEB_ACCESS = 'web_access';

    /**
     * @param  list<string>  $scopes
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $issuer,
        public string $audience,
        public string $subject,
        public string $siteId,
        public string $tokenType,
        public array $scopes,
        public array $permissions,
        public string $jti,
        public int $issuedAt,
        public int $notBefore,
        public int $expiresAt,
        public ?string $nonce = null,
    ) {
        if (! in_array($this->tokenType, self::supportedTokenTypes(), true)) {
            throw new InvalidArgumentException('unsupported_token_type');
        }

        if ($this->tokenType === self::TOKEN_TYPE_ID_TOKEN && trim((string) $this->nonce) === '') {
            throw new InvalidArgumentException('nonce_required_for_id_token');
        }
    }

    /**
     * @return list<string>
     */
    public static function supportedTokenTypes(): array
    {
        return [
            self::TOKEN_TYPE_USER_API,
            self::TOKEN_TYPE_ID_TOKEN,
            self::TOKEN_TYPE_WEB_ACCESS,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $this->subject,
            'site_id' => $this->siteId,
            'token_type' => $this->tokenType,
            'scope' => implode(' ', $this->scopes),
        ];

        if ($this->permissions !== []) {
            $payload['permissions'] = $this->permissions;
        }

        if ($this->nonce !== null) {
            $payload['nonce'] = $this->nonce;
        }

        $payload['jti'] = $this->jti;
        $payload['iat'] = $this->issuedAt;
        $payload['nbf'] = $this->notBefore;
        $payload['exp'] = $this->expiresAt;

        return $payload;
    }
}
