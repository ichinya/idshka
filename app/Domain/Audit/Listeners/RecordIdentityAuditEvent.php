<?php

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Identity\Events\PasswordLoginSucceeded;
use App\Domain\Identity\Events\SocialAccountLinked;
use App\Domain\Identity\Events\SocialAccountUnlinked;
use App\Domain\Identity\Events\SocialLoginSucceeded;
use Illuminate\Support\Facades\Log;

final class RecordIdentityAuditEvent
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    public function handle(
        PasswordLoginSucceeded|SocialLoginSucceeded|SocialAccountLinked|SocialAccountUnlinked $event
    ): void {
        if ($event instanceof PasswordLoginSucceeded) {
            $this->auditRecorder->record(
                category: 'identity',
                action: 'identity.password_login_succeeded',
                userId: (int) $event->user->id,
                siteId: null,
                summary: 'Password login succeeded',
                metadata: [
                    'via_registration' => $event->viaRegistration,
                ],
            );

            Log::info('[audit.identity] password login succeeded', [
                'user_id' => $event->user->id,
                'via_registration' => $event->viaRegistration,
            ]);

            return;
        }

        if ($event instanceof SocialLoginSucceeded) {
            $this->auditRecorder->record(
                category: 'identity',
                action: 'identity.social_login_succeeded',
                userId: (int) $event->user->id,
                siteId: null,
                summary: 'Social login succeeded',
                metadata: [
                    'provider' => $event->provider->value,
                    'linked_during_login' => $event->linkedDuringLogin,
                ],
            );

            Log::info('[audit.identity] social login succeeded', [
                'user_id' => $event->user->id,
                'provider' => $event->provider->value,
                'linked_during_login' => $event->linkedDuringLogin,
            ]);

            return;
        }

        if ($event instanceof SocialAccountLinked) {
            $this->auditRecorder->record(
                category: 'identity',
                action: 'identity.social_account_linked',
                userId: (int) $event->user->id,
                siteId: null,
                summary: 'Social account linked',
                metadata: [
                    'provider' => $event->provider->value,
                    'provider_user_id' => $event->providerUserId,
                ],
            );

            Log::info('[audit.identity] social account linked', [
                'user_id' => $event->user->id,
                'provider' => $event->provider->value,
                'provider_user_id' => $event->providerUserId,
            ]);

            return;
        }

        $this->auditRecorder->record(
            category: 'identity',
            action: 'identity.social_account_unlinked',
            userId: (int) $event->user->id,
            siteId: null,
            summary: 'Social account unlinked',
            metadata: [
                'provider' => $event->provider->value,
            ],
        );

        Log::info('[audit.identity] social account unlinked', [
            'user_id' => $event->user->id,
            'provider' => $event->provider->value,
        ]);
    }
}
