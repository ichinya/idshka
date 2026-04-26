<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Enums\SocialProvider;
use Carbon\CarbonImmutable;

final readonly class SocialProfile
{
    public function __construct(
        public SocialProvider $provider,
        public string $providerUserId,
        public ?string $email,
        public ?string $name,
        public ?string $avatarUrl,
        public ?string $accessToken,
        public ?string $refreshToken,
        public ?CarbonImmutable $expiresAt,
    ) {}
}
