<?php

namespace App\Http\Controllers\Portal\Audit;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AuditEventController extends Controller
{
    public function show(Request $request, AuditEvent $event): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $userId = (int) $user->getAuthIdentifier();
        $ownsEventSite = $event->site_id !== null
            && Site::query()
                ->whereKey($event->site_id)
                ->where('owner_user_id', $userId)
                ->exists();

        abort_unless($event->user_id === $userId || $ownsEventSite, 404);

        return view('portal.audit.show', [
            'event' => $event->load(['user', 'site']),
            'metadataJson' => json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
        ]);
    }
}
