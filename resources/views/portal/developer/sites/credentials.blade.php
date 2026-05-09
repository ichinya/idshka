@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Credentials')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Credentials',
        'subtitle' => $site->normalized_domain,
    ])

    @if (session('issued_client'))
        @component('portal.components.warning-callout', ['title' => 'Secrets are shown only once'])
            Copy and store this client secret now. Later pages show only the client id and non-secret metadata.
        @endcomponent
        <div class="rounded-md border border-amber-300 bg-amber-50 p-4">
            <dl class="grid gap-2 text-sm md:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-600">Client ID</dt>
                    <dd>{{ session('issued_client.client_id') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-600">Client secret</dt>
                    <dd class="break-all font-mono text-xs">{{ session('issued_client.client_secret') }}</dd>
                </div>
            </dl>
        </div>
    @else
        @component('portal.components.warning-callout', ['title' => 'Secrets are shown only once'])
            New client secrets are displayed only after creation. Store them before leaving the page.
        @endcomponent
    @endif

    @component('portal.components.card', ['title' => 'Create web client'])
        <form class="grid gap-3" method="POST" action="{{ route('portal.clients.store') }}">
            @csrf
            <input type="hidden" name="site_id" value="{{ $site->id }}">
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Name</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" name="name" value="{{ old('name', 'Example Web') }}" required>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Redirect URI</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" name="redirect_uri" value="{{ old('redirect_uri', 'https://'.$site->normalized_domain.'/auth/idshka/callback') }}" placeholder="https://{{ $site->normalized_domain }}/auth/idshka/callback" required>
            </label>
            <button class="w-fit rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800" type="submit">
                Create client
            </button>
        </form>
    @endcomponent

    @component('portal.components.card', ['title' => 'Web clients'])
        <div class="divide-y divide-zinc-200">
            @forelse ($clients as $client)
                <div class="grid gap-3 py-4 text-sm md:grid-cols-[1fr_auto]">
                    <div>
                        <p class="font-medium">{{ $client->name }} · {{ $client->client_id }}</p>
                        <p class="text-xs text-zinc-600">{{ $client->redirectUris->count() }} redirect URI(s)</p>
                        @if ($client->redirectUris->isNotEmpty())
                            <ul class="mt-2 grid gap-1">
                                @foreach ($client->redirectUris as $redirectUri)
                                    <li class="break-all font-mono text-xs text-zinc-600">{{ $redirectUri->redirect_uri }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @include('portal.components.status-badge', ['variant' => $client->revoked_at ? 'revoked' : 'active', 'label' => $client->revoked_at ? 'revoked' : 'active'])
                    </div>
                    <form class="flex gap-2" method="POST" action="{{ route('portal.clients.revoke', $client) }}">
                        @csrf
                        <input class="w-24 rounded-md border border-zinc-300 px-2 py-1 text-xs" name="confirm" placeholder="revoke">
                        <button class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50" type="submit">
                            Revoke
                        </button>
                    </form>
                </div>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No web clients',
                    'text' => 'Create a web client to enable Authorization Code + PKCE login through idshka.ru.',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
