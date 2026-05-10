<?php

namespace App\Http\Controllers\Portal\Developer;

use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SiteCredentialController extends Controller
{
    public function index(Request $request, Site $site): View
    {
        $user = $request->user();
        abort_unless($user !== null && $site->owner_user_id === (int) $user->getAuthIdentifier(), 404);

        return view('portal.developer.sites.credentials', [
            'site' => $site->load('modes'),
            'clients' => OidcClient::query()
                ->with('redirectUris')
                ->where('site_id', $site->id)
                ->where('owner_user_id', (int) $user->getAuthIdentifier())
                ->latest()
                ->get(),
        ]);
    }
}
