<?php

namespace App\Http\Controllers\Portal\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SessionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $sessionStore = (string) config('session.driver');
        $sessionTable = (string) config('session.table', 'sessions');
        $sessionsAreEnumerable = $sessionStore === 'database' && Schema::hasTable($sessionTable);

        $sessions = $sessionsAreEnumerable
            ? DB::table($sessionTable)
                ->where('user_id', (int) $user->getAuthIdentifier())
                ->orderByDesc('last_activity')
                ->limit(20)
                ->get()
            : collect();

        return view('portal.account.sessions.index', [
            'currentSessionId' => $request->session()->getId(),
            'sessionStore' => $sessionStore,
            'sessionsAreEnumerable' => $sessionsAreEnumerable,
            'sessions' => $sessions,
        ]);
    }
}
