<?php

namespace App\Domain\Audit\Listeners;

use App\Domain\Identity\Events\PasswordLoginSucceeded;
use App\Domain\Identity\Events\SocialAccountLinked;
use App\Domain\Identity\Events\SocialAccountUnlinked;
use App\Domain\Identity\Events\SocialLoginSucceeded;
use Illuminate\Support\Facades\Log;

final class RecordIdentityAuditEvent
{
    public function handle(
        PasswordLoginSucceeded|SocialLoginSucceeded|SocialAccountLinked|SocialAccountUnlinked $event
    ): void {
        if ($event instanceof PasswordLoginSucceeded) {
            Log::info('[audit.identity] password login succeeded', [
                'user_id' => $event->user->id,
                'via_registration' => $event->viaRegistration,
            ]);

            return;
        }

        if ($event instanceof SocialLoginSucceeded) {
            Log::info('[audit.identity] social login succeeded', [
                'user_id' => $event->user->id,
                'provider' => $event->provider->value,
                'linked_during_login' => $event->linkedDuringLogin,
            ]);

            return;
        }

        if ($event instanceof SocialAccountLinked) {
            Log::info('[audit.identity] social account linked', [
                'user_id' => $event->user->id,
                'provider' => $event->provider->value,
                'provider_user_id' => $event->providerUserId,
            ]);

            return;
        }

        Log::info('[audit.identity] social account unlinked', [
            'user_id' => $event->user->id,
            'provider' => $event->provider->value,
        ]);
    }
}
