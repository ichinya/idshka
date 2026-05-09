<?php

namespace App\Http\Controllers\Portal\Audit;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $userId = (int) $user->getAuthIdentifier();
        $ownedSites = Site::query()
            ->where('owner_user_id', $userId)
            ->orderBy('normalized_domain')
            ->get();
        $ownedSiteIds = $ownedSites->pluck('id')->all();

        $events = AuditEvent::query()
            ->where(function (Builder $query) use ($userId, $ownedSiteIds): void {
                $query->where('user_id', $userId)
                    ->orWhereIn('site_id', $ownedSiteIds);
            })
            ->when($request->filled('category'), fn (Builder $query): Builder => $query->where('category', (string) $request->string('category')))
            ->when($request->filled('action'), fn (Builder $query): Builder => $query->where('action', (string) $request->string('action')))
            ->when($request->filled('site_id'), fn (Builder $query): Builder => $query->where('site_id', (string) $request->string('site_id')))
            ->when($request->filled('actor'), fn (Builder $query): Builder => $query->where('user_id', (int) $request->integer('actor')))
            ->when($request->filled('from'), fn (Builder $query): Builder => $query->where('occurred_at', '>=', (string) $request->string('from')))
            ->when($request->filled('to'), fn (Builder $query): Builder => $query->where('occurred_at', '<=', (string) $request->string('to')))
            ->latest('occurred_at')
            ->limit(100)
            ->get();

        return view('portal.audit.index', [
            'events' => $events,
            'ownedSites' => $ownedSites,
            'filters' => $request->only(['category', 'action', 'site_id', 'actor', 'from', 'to', 'severity']),
        ]);
    }
}
