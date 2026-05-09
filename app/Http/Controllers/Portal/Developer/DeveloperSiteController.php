<?php

namespace App\Http\Controllers\Portal\Developer;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DeveloperSiteController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return view('portal.developer.sites.index', [
            'sites' => Site::query()
                ->with(['modes', 'verifications'])
                ->where('owner_user_id', (int) $user->getAuthIdentifier())
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('portal.developer.sites.create');
    }

    public function show(Request $request, Site $site): View
    {
        $this->authorizeOwner($request, $site);

        $site->load(['modes', 'verifications']);

        return view('portal.developer.sites.show', [
            'site' => $site,
            'clients' => OidcClient::query()
                ->with('redirectUris')
                ->where('site_id', $site->id)
                ->latest()
                ->get(),
            'recentEvents' => AuditEvent::query()
                ->where('site_id', $site->id)
                ->latest('occurred_at')
                ->limit(8)
                ->get(),
        ]);
    }

    private function authorizeOwner(Request $request, Site $site): void
    {
        $user = $request->user();
        abort_unless($user !== null && $site->owner_user_id === (int) $user->getAuthIdentifier(), 404);
    }
}
