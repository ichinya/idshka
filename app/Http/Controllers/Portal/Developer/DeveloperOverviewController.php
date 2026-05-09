<?php

namespace App\Http\Controllers\Portal\Developer;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DeveloperOverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $userId = (int) $user->getAuthIdentifier();
        $sites = Site::query()
            ->with('modes')
            ->where('owner_user_id', $userId)
            ->latest()
            ->get();
        $siteIds = $sites->pluck('id')->all();

        return view('portal.developer.overview', [
            'sites' => $sites,
            'verifiedCount' => $sites->where('verification_status', SiteVerificationStatus::Verified->value)->count(),
            'activeClientCount' => OidcClient::query()
                ->where('owner_user_id', $userId)
                ->whereNull('revoked_at')
                ->count(),
            'recentEvents' => AuditEvent::query()
                ->whereIn('site_id', $siteIds)
                ->latest('occurred_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
