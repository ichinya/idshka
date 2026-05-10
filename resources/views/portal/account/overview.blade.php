@extends('layouts.portal', ['activeWorkspace' => 'account'])

@section('title', 'Account')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Account',
        'subtitle' => 'Identity, sessions, personal tokens, and recent account activity.',
    ])

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @component('portal.components.card', ['title' => 'Profile'])
            <dl class="grid gap-2 text-sm">
                <div>
                    <dt class="text-zinc-600">Name</dt>
                    <dd class="font-medium">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-600">Email</dt>
                    <dd class="font-medium">{{ $user->email }}</dd>
                </div>
            </dl>
        @endcomponent

        @component('portal.components.card', ['title' => 'Social accounts'])
            <p class="text-3xl font-semibold">{{ $socialAccounts->count() }}</p>
            <a class="mt-3 inline-flex text-sm font-medium text-cyan-700 hover:text-cyan-900" href="{{ route('portal.account.social.index') }}">Manage providers</a>
        @endcomponent

        @component('portal.components.card', ['title' => 'Active API tokens'])
            <p class="text-3xl font-semibold">{{ $activeTokenCount }}</p>
            <a class="mt-3 inline-flex text-sm font-medium text-cyan-700 hover:text-cyan-900" href="{{ route('portal.account.tokens.index') }}">Manage tokens</a>
        @endcomponent

        @component('portal.components.card', ['title' => 'Connected sites'])
            <p class="text-3xl font-semibold">{{ $sites->count() }}</p>
            <a class="mt-3 inline-flex text-sm font-medium text-cyan-700 hover:text-cyan-900" href="{{ route('portal.developer.sites.index') }}">Open Developer</a>
        @endcomponent
    </section>

    @component('portal.components.card', ['title' => 'Recent account activity'])
        <div class="divide-y divide-zinc-200">
            @forelse ($recentEvents as $event)
                <a class="grid gap-1 py-3 text-sm hover:bg-zinc-50" href="{{ route('portal.audit.show', $event) }}">
                    <span class="font-mono text-xs text-zinc-600">{{ $event->action }}</span>
                    <span class="font-medium">{{ $event->summary }}</span>
                    <span class="text-xs text-zinc-600">{{ $event->occurred_at?->toISOString() }}</span>
                </a>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No account activity yet',
                    'text' => 'Login, social, token, and site actions will appear here.',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
