<?php

namespace App\Domain\Identity\Enums;

enum SocialProvider: string
{
    case Google = 'google';
    case VKontakte = 'vk';
    case Yandex = 'yandex';

    public static function tryFromRoute(string $provider): ?self
    {
        $normalized = strtolower(trim($provider));

        return match ($normalized) {
            'vkontakte' => self::VKontakte,
            default => self::tryFrom($normalized),
        };
    }

    public function driver(): string
    {
        return match ($this) {
            self::VKontakte => 'vkontakte',
            default => $this->value,
        };
    }

    /**
     * @return array<int, string>
     */
    public function scopes(): array
    {
        return match ($this) {
            self::Google => ['openid', 'profile', 'email'],
            self::VKontakte => ['email'],
            self::Yandex => ['login:email', 'login:info'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::VKontakte => 'VK',
            self::Yandex => 'Yandex',
        };
    }
}
