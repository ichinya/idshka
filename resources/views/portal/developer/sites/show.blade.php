@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', $site->normalized_domain)

@section('content')
    @include('portal.components.page-header', [
        'title' => $site->display_name ?: $site->normalized_domain,
        'subtitle' => $site->normalized_domain,
    ])

    @php
        $dnsVerification = $site->verifications->where('method', 'dns_txt')->sortByDesc('id')->first();
        $fileVerification = $site->verifications->where('method', 'file')->sortByDesc('id')->first();
    @endphp

    <section class="grid gap-4 md:grid-cols-3">
        @component('portal.components.card', ['title' => 'Verification'])
            @include('portal.components.status-badge', ['variant' => $site->isVerified() ? 'verified' : 'unverified', 'label' => $site->verification_status])
            <a class="mt-3 block text-sm font-medium text-cyan-700 hover:text-cyan-900" href="{{ route('portal.developer.sites.verification.show', $site) }}">Open verification</a>
        @endcomponent
        @component('portal.components.card', ['title' => 'Modes'])
            <div class="flex flex-wrap gap-2">
                @forelse ($site->modes as $mode)
                    @include('portal.components.status-badge', ['variant' => 'info', 'label' => $mode->mode])
                @empty
                    @include('portal.components.status-badge', ['variant' => 'neutral', 'label' => 'none'])
                @endforelse
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('portal.sites.modes.store', [$site, 'api_resource']) }}">
                    @csrf
                    <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" type="submit">API resource</button>
                </form>
                <form method="POST" action="{{ route('portal.sites.modes.store', [$site, 'web_client']) }}">
                    @csrf
                    <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" type="submit">Web client</button>
                </form>
            </div>
        @endcomponent
        @component('portal.components.card', ['title' => 'Credentials'])
            <p class="text-3xl font-semibold">{{ $clients->count() }}</p>
            <a class="mt-3 block text-sm font-medium text-cyan-700 hover:text-cyan-900" href="{{ route('portal.developer.sites.credentials.index', $site) }}">Open credentials</a>
        @endcomponent
    </section>

    @component('portal.components.card', ['title' => 'Verification instructions'])
        <div class="grid gap-4 text-sm md:grid-cols-2">
            <dl class="grid gap-2">
                <div>
                    <dt class="font-medium text-zinc-600">DNS TXT host</dt>
                    <dd class="break-all font-mono text-xs">{{ '_idshka.'.$site->normalized_domain }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-600">DNS TXT value</dt>
                    <dd class="break-all font-mono text-xs">idshka-site-verification={{ $dnsVerification?->token }}</dd>
                </div>
            </dl>
            <dl class="grid gap-2">
                <div>
                    <dt class="font-medium text-zinc-600">Well-known file URL</dt>
                    <dd class="break-all font-mono text-xs">https://{{ $site->normalized_domain }}/.well-known/idshka-site-verification.txt</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-600">File content</dt>
                    <dd class="break-all font-mono text-xs">{{ $fileVerification?->token }}</dd>
                </div>
            </dl>
        </div>
    @endcomponent

    @component('portal.components.card', ['title' => 'Integration'])
        <div class="flex flex-wrap gap-2">
            <a class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" href="{{ route('portal.developer.sites.gateway', $site) }}">Gateway guide</a>
            <a class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" href="{{ route('portal.developer.sites.web-login', $site) }}">Web Login guide</a>
            <a class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" href="{{ route('portal.developer.sites.redirect-uris.index', $site) }}">Redirect URIs</a>
        </div>
    @endcomponent

    @component('portal.components.card', ['title' => 'Recent site events'])
        <div class="divide-y divide-zinc-200">
            @forelse ($recentEvents as $event)
                <a class="grid gap-1 py-3 text-sm hover:bg-zinc-50" href="{{ route('portal.audit.show', $event) }}">
                    <span class="font-mono text-xs text-zinc-600">{{ $event->action }}</span>
                    <span>{{ $event->summary }}</span>
                </a>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No site events',
                    'text' => 'Verification, mode, credential, and redirect URI events will appear here.',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
