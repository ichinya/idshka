<?php

namespace App\Contracts\Auth;

final class Permissions
{
    public const ORDERS_READ = 'orders.read';

    public const ORDERS_WRITE = 'orders.write';

    public const PROFILE_READ = 'profile.read';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ORDERS_READ,
            self::ORDERS_WRITE,
            self::PROFILE_READ,
        ];
    }
}
