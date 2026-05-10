<?php

namespace App\Http\Controllers\Portal\Account;

use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class UserTokenController extends Controller
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

        return view('portal.account.tokens.index', [
            'apiResourceSites' => $sites
                ->filter(fn (Site $site): bool => $site->modes->contains('mode', SiteModeType::ApiResource->value))
                ->values(),
            'apiTokens' => ApiToken::query()
                ->with('site')
                ->where('user_id', $userId)
                ->latest()
                ->get(),
        ]);
    }
}
