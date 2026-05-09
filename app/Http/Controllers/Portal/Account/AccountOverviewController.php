<?php

namespace App\Http\Controllers\Portal\Account;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AccountOverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $userId = (int) $user->getAuthIdentifier();
        $sites = Site::query()
            ->where('owner_user_id', $userId)
            ->latest()
            ->limit(5)
            ->get();
        $apiTokens = ApiToken::query()
            ->with('site')
            ->where('user_id', $userId)
            ->latest()
            ->limit(5)
            ->get();
        $recentEvents = AuditEvent::query()
            ->where('user_id', $userId)
            ->latest('occurred_at')
            ->limit(8)
            ->get();

        return view('portal.account.overview', [
            'user' => $user,
            'socialAccounts' => $user->socialAccounts()->latest()->get(),
            'sites' => $sites,
            'apiTokens' => $apiTokens,
            'recentEvents' => $recentEvents,
            'activeTokenCount' => ApiToken::query()
                ->where('user_id', $userId)
                ->whereNull('revoked_at')
                ->count(),
        ]);
    }
}
