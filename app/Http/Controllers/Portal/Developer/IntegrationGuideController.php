<?php

namespace App\Http\Controllers\Portal\Developer;

use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class IntegrationGuideController extends Controller
{
    public function gateway(Request $request, Site $site): View
    {
        $this->authorizeOwner($request, $site);

        return view('portal.developer.sites.gateway', [
            'site' => $site->load('modes'),
        ]);
    }

    public function webLogin(Request $request, Site $site): View
    {
        $this->authorizeOwner($request, $site);

        return view('portal.developer.sites.web-login', [
            'site' => $site->load('modes'),
        ]);
    }

    private function authorizeOwner(Request $request, Site $site): void
    {
        $user = $request->user();
        abort_unless($user !== null && $site->owner_user_id === (int) $user->getAuthIdentifier(), 404);
    }
}
