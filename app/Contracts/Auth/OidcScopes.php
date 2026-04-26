<?php

namespace App\Contracts\Auth;

final class OidcScopes
{
    public const OPENID = 'openid';

    public const PROFILE = 'profile';

    public const EMAIL = 'email';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::OPENID,
            self::PROFILE,
            self::EMAIL,
        ];
    }
}
