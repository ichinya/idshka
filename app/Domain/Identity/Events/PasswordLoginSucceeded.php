<?php

namespace App\Domain\Identity\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PasswordLoginSucceeded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly bool $viaRegistration,
    ) {}
}
