<?php

namespace App\Http\Controllers\Portal\Developer;

use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SiteVerificationController extends Controller
{
    public function show(Request $request, Site $site): View
    {
        $user = $request->user();
        abort_unless($user !== null && $site->owner_user_id === (int) $user->getAuthIdentifier(), 404);

        return view('portal.developer.sites.verification', [
            'site' => $site->load('verifications'),
        ]);
    }
}
