<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Enums\SocialProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final class SocialiteProfileFactory
{
    private const PROVIDER_USER_ID_MAX_LENGTH = 191;

    public function make(SocialProvider $provider, SocialiteUser $socialiteUser): SocialProfile
    {
        $providerUserId = trim((string) $socialiteUser->getId());

        if ($providerUserId === '') {
            throw new InvalidArgumentException('provider_user_id_missing');
        }

        if (mb_strlen($providerUserId) > self::PROVIDER_USER_ID_MAX_LENGTH) {
            throw new InvalidArgumentException('provider_user_id_too_long');
        }

        $expiresAt = null;
        $expiresIn = $socialiteUser->expiresIn ?? null;

        if (is_numeric($expiresIn) && (int) $expiresIn > 0) {
            $expiresAt = CarbonImmutable::now()->addSeconds((int) $expiresIn);
        }

        return new SocialProfile(
            provider: $provider,
            providerUserId: $providerUserId,
            email: $this->normalizeEmail($socialiteUser->getEmail()),
            name: $this->sanitizeNullableString($socialiteUser->getName(), 255),
            avatarUrl: $this->sanitizeNullableString($socialiteUser->getAvatar(), 2048),
            accessToken: $this->sanitizeNullableString($socialiteUser->token ?? null, 8192),
            refreshToken: $this->sanitizeNullableString($socialiteUser->refreshToken ?? null, 8192),
            expiresAt: $expiresAt,
        );
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = $this->sanitizeNullableString($email, 255);

        return $normalized !== null ? mb_strtolower($normalized) : null;
    }

    private function sanitizeNullableString(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return Str::limit($trimmed, $maxLength, '');
    }
}
