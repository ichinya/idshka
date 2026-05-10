@extends('layouts.portal', ['activeWorkspace' => 'account'])

@section('title', 'Sessions')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Sessions',
        'subtitle' => 'Current session and safe metadata from the Laravel session store.',
    ])

    @component('portal.components.card', ['title' => 'Current session'])
        <dl class="grid gap-2 text-sm md:grid-cols-2">
            <div>
                <dt class="text-zinc-600">Session ID</dt>
                <dd class="break-all font-mono text-xs">{{ $currentSessionId }}</dd>
            </div>
            <div>
                <dt class="text-zinc-600">Tracked sessions</dt>
                <dd class="font-medium">{{ $sessionsAreEnumerable ? $sessions->count() : 'Not listed' }}</dd>
            </div>
            <div>
                <dt class="text-zinc-600">Session store</dt>
                <dd class="font-medium">{{ $sessionStore }}</dd>
            </div>
        </dl>
    @endcomponent

    @component('portal.components.card', ['title' => 'Stored sessions'])
        <div class="divide-y divide-zinc-200">
            @forelse ($sessions as $session)
                <div class="grid gap-1 py-3 text-sm md:grid-cols-[1fr_12rem]">
                    <div>
                        <p class="break-all font-mono text-xs">{{ $session->id }}</p>
                        <p class="text-zinc-600">{{ $session->user_agent ?: 'Unknown device' }}</p>
                        <p class="text-xs text-zinc-600">{{ $session->ip_address ?: 'Unknown IP' }}</p>
                    </div>
                    <p class="text-xs text-zinc-600 md:text-right">Last active {{ $session->last_activity }}</p>
                </div>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No stored sessions',
                    'text' => $sessionsAreEnumerable
                        ? 'The current session is active. Additional database session rows will appear here when available.'
                        : 'The current session is active. This session store does not expose a safe session listing.',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
