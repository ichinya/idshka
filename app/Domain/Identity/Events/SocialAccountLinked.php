<?php

namespace App\Domain\Identity\Events;

use App\Domain\Identity\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SocialAccountLinked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly SocialProvider $provider,
        public readonly string $providerUserId,
    ) {}
}
