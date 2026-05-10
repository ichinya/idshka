@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Developer')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Developer',
        'subtitle' => 'Connected sites, verification, credentials, redirect URIs, and integration guides.',
    ])

    <section class="grid gap-4 md:grid-cols-3">
        @component('portal.components.card', ['title' => 'Connected sites'])
            <p class="text-3xl font-semibold">{{ $sites->count() }}</p>
            <a class="mt-3 inline-flex text-sm font-medium text-cyan-700 hover:text-cyan-900" href="{{ route('portal.developer.sites.index') }}">Manage sites</a>
        @endcomponent
        @component('portal.components.card', ['title' => 'Verified domains'])
            <p class="text-3xl font-semibold">{{ $verifiedCount }}</p>
        @endcomponent
        @component('portal.components.card', ['title' => 'Active clients'])
            <p class="text-3xl font-semibold">{{ $activeClientCount }}</p>
        @endcomponent
    </section>

    @component('portal.components.card', ['title' => 'Sites'])
        <div class="divide-y divide-zinc-200">
            @forelse ($sites as $site)
                <a class="grid gap-2 py-3 text-sm hover:bg-zinc-50 md:grid-cols-[1fr_auto]" href="{{ route('portal.developer.sites.show', $site) }}">
                    <span>
                        <span class="block font-medium">{{ $site->display_name ?: $site->normalized_domain }}</span>
                        <span class="block text-zinc-600">{{ $site->normalized_domain }}</span>
                    </span>
                    <span class="flex flex-wrap gap-2 md:justify-end">
                        @include('portal.components.status-badge', ['variant' => $site->isVerified() ? 'verified' : 'unverified', 'label' => $site->verification_status])
                        @foreach ($site->modes as $mode)
                            @include('portal.components.status-badge', ['variant' => 'info', 'label' => $mode->mode])
                        @endforeach
                    </span>
                </a>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No connected sites',
                    'text' => 'Add a site, verify ownership, and choose API-only or Web Login mode.',
                    'actionHref' => route('portal.developer.sites.create'),
                    'actionLabel' => 'Add site',
                ])
            @endforelse
        </div>
    @endcomponent

    @component('portal.components.card', ['title' => 'Recent developer events'])
        <div class="divide-y divide-zinc-200">
            @forelse ($recentEvents as $event)
                <a class="grid gap-1 py-3 text-sm hover:bg-zinc-50" href="{{ route('portal.audit.show', $event) }}">
                    <span class="font-mono text-xs text-zinc-600">{{ $event->action }}</span>
                    <span class="font-medium">{{ $event->summary }}</span>
                </a>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No developer events',
                    'text' => 'Site, credential, redirect URI, and verification events will appear here.',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
