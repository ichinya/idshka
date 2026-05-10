<?php

namespace App\Http\Controllers\Portal\Audit;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

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
        $from = $this->dateBound($request, 'from', 'start');
        $to = $this->dateBound($request, 'to', 'end');

        $events = AuditEvent::query()
            ->where(function (Builder $query) use ($userId, $ownedSiteIds): void {
                $query->where('user_id', $userId)
                    ->orWhereIn('site_id', $ownedSiteIds);
            })
            ->when($request->filled('category'), fn (Builder $query): Builder => $query->where('category', (string) $request->string('category')))
            ->when($request->filled('action'), fn (Builder $query): Builder => $query->where('action', (string) $request->string('action')))
            ->when($request->filled('site_id'), fn (Builder $query): Builder => $query->where('site_id', (string) $request->string('site_id')))
            ->when($request->filled('actor'), fn (Builder $query): Builder => $query->where('user_id', (int) $request->integer('actor')))
            ->when($from !== null, fn (Builder $query): Builder => $query->where('occurred_at', '>=', $from))
            ->when($to !== null, fn (Builder $query): Builder => $query->where('occurred_at', '<=', $to))
            ->latest('occurred_at')
            ->limit(100)
            ->get();

        return view('portal.audit.index', [
            'events' => $events,
            'ownedSites' => $ownedSites,
            'filters' => $request->only(['category', 'action', 'site_id', 'actor', 'from', 'to', 'severity']),
        ]);
    }

    private function dateBound(Request $request, string $key, string $bound): ?CarbonImmutable
    {
        if (! $request->filled($key)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', (string) $request->string($key));
        } catch (Throwable) {
            return null;
        }

        if ($date === null || $date === false) {
            return null;
        }

        return $bound === 'end' ? $date->endOfDay() : $date->startOfDay();
    }
}
