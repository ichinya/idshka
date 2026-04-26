<?php

namespace App\Domain\Identity\Services;

use App\Models\User;

final readonly class SocialCallbackResult
{
    public function __construct(
        public User $user,
        public bool $linkingFlow,
        public bool $linked,
        public bool $createdUser,
    ) {}
}
