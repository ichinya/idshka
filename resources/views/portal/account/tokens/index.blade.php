@extends('layouts.portal', ['activeWorkspace' => 'account'])

@section('title', 'API tokens')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'API tokens',
        'subtitle' => 'Create and revoke user API tokens for verified API-only sites.',
    ])

    @if (session('issued_api_token'))
        @component('portal.components.warning-callout', ['title' => 'Token is shown only once'])
            Copy and store this token now. Later pages show only token metadata.
        @endcomponent
        <div class="rounded-md border border-amber-300 bg-amber-50 p-4">
            <dl class="grid gap-2 text-sm md:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-600">Token ID</dt>
                    <dd>{{ session('issued_api_token.token_id') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-600">Expires</dt>
                    <dd>{{ session('issued_api_token.expires_at') }}</dd>
                </div>
            </dl>
            <pre class="mt-3 overflow-x-auto rounded-md bg-zinc-950 p-3 text-xs text-zinc-50">{{ session('issued_api_token.token') }}</pre>
        </div>
    @endif

    @component('portal.components.card', ['title' => 'Issue token'])
        @if ($apiResourceSites->isNotEmpty())
            @php
                $selectedSiteId = old('site_id');
                $siteIds = $apiResourceSites->pluck('id')->all();

                if (! in_array($selectedSiteId, $siteIds, true)) {
                    $selectedSiteId = $apiResourceSites->first()?->id;
                }
            @endphp
            <form class="grid gap-3 md:grid-cols-2" method="POST" action="{{ route('portal.api-tokens.store') }}">
                @csrf
                <label class="grid gap-1 text-sm md:col-span-2">
                    <span class="font-medium">Site</span>
                    <select class="rounded-md border border-zinc-300 px-3 py-2" name="site_id" required>
                        @foreach ($apiResourceSites as $site)
                            <option value="{{ $site->id }}" @selected($site->id === $selectedSiteId)>{{ $site->normalized_domain }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Scopes</span>
                    <input class="rounded-md border border-zinc-300 px-3 py-2" name="scopes" value="{{ old('scopes', 'orders.read') }}">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Permissions</span>
                    <input class="rounded-md border border-zinc-300 px-3 py-2" name="permissions" value="{{ old('permissions', 'orders.read') }}">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Expiration</span>
                    <select class="rounded-md border border-zinc-300 px-3 py-2" name="expires_mode">
                        <option value="default" @selected(old('expires_mode', 'default') === 'default')>Default TTL</option>
                        <option value="at" @selected(old('expires_mode') === 'at')>At date/time</option>
                        <option value="never" @selected(old('expires_mode') === 'never')>No expiration</option>
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Expires at</span>
                    <input class="rounded-md border border-zinc-300 px-3 py-2" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}">
                </label>
                <button class="rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800 md:col-span-2" type="submit">
                    Issue token
                </button>
            </form>
        @else
            @include('portal.components.empty-state', [
                'title' => 'No API resource sites',
                'text' => 'Enable API resource mode on a verified site before issuing tokens.',
                'actionHref' => route('portal.developer.sites.index'),
                'actionLabel' => 'Open sites',
            ])
        @endif
    @endcomponent

    @component('portal.components.card', ['title' => 'Token metadata'])
        <div class="divide-y divide-zinc-200">
            @forelse ($apiTokens as $token)
                <div class="grid gap-3 py-3 text-sm md:grid-cols-[1fr_auto]">
                    <div>
                        <p class="font-medium">{{ $token->site?->normalized_domain }} · {{ $token->audience }}</p>
                        <p class="font-mono text-xs text-zinc-600">{{ $token->jti }}</p>
                        <p class="text-xs text-zinc-600">Expires {{ $token->expires_at?->toISOString() ?? 'Never' }}</p>
                        @include('portal.components.status-badge', ['variant' => $token->revoked_at ? 'revoked' : 'active', 'label' => $token->revoked_at ? 'revoked' : 'active'])
                    </div>
                    <form class="flex gap-2" method="POST" action="{{ route('portal.api-tokens.revoke', $token) }}">
                        @csrf
                        <input class="w-24 rounded-md border border-zinc-300 px-2 py-1 text-xs" name="confirm" placeholder="revoke">
                        <button class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50" type="submit">
                            Revoke
                        </button>
                    </form>
                </div>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No API tokens',
                    'text' => 'Issued user API tokens will appear here without raw token material.',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
