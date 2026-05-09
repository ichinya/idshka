<?php

namespace App\Http\Controllers\Portal\Account;

use App\Domain\Identity\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SocialAccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return view('portal.account.social.index', [
            'providers' => SocialProvider::cases(),
            'linkedAccounts' => $user->socialAccounts()->latest()->get()->keyBy('provider'),
        ]);
    }
}
