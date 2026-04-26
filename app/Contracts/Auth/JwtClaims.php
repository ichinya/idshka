<?php

namespace App\Contracts\Auth;

use InvalidArgumentException;

final readonly class JwtClaims
{
    public const TOKEN_TYPE_USER_API = 'user_api';

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
    ) {
        if ($this->tokenType !== self::TOKEN_TYPE_USER_API) {
            throw new InvalidArgumentException('unsupported_token_type');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $this->subject,
            'site_id' => $this->siteId,
            'token_type' => $this->tokenType,
            'scope' => implode(' ', $this->scopes),
            'permissions' => $this->permissions,
            'jti' => $this->jti,
            'iat' => $this->issuedAt,
            'nbf' => $this->notBefore,
            'exp' => $this->expiresAt,
        ];
    }
}
