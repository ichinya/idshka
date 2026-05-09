<?php

namespace App\Http\Controllers\Portal\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SessionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $sessions = DB::table('sessions')
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->limit(20)
            ->get();

        return view('portal.account.sessions.index', [
            'currentSessionId' => $request->session()->getId(),
            'sessions' => $sessions,
        ]);
    }
}
