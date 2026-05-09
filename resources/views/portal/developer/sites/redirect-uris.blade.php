@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Redirect URIs')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Redirect URIs',
        'subtitle' => $site->normalized_domain,
    ])

    @component('portal.components.card', ['title' => 'Registered redirect URIs'])
        <div class="divide-y divide-zinc-200">
            @forelse ($clients as $client)
                <div class="py-4">
                    <h2 class="text-sm font-semibold">{{ $client->name }} · {{ $client->client_id }}</h2>
                    <ul class="mt-3 grid gap-2 text-sm">
                        @forelse ($client->redirectUris as $redirectUri)
                            <li class="break-all rounded-md bg-zinc-50 px-3 py-2 font-mono text-xs">{{ $redirectUri->redirect_uri }}</li>
                        @empty
                            <li class="text-zinc-600">No redirect URIs.</li>
                        @endforelse
                    </ul>
                    <form class="mt-3 flex flex-col gap-2 md:flex-row" method="POST" action="{{ route('portal.clients.redirect-uris.store', $client) }}">
                        @csrf
                        <input class="min-w-0 flex-1 rounded-md border border-zinc-300 px-3 py-2 text-sm" name="redirect_uri" placeholder="https://{{ $site->normalized_domain }}/auth/idshka/callback">
                        <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" type="submit">
                            Add redirect URI
                        </button>
                    </form>
                </div>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No clients yet',
                    'text' => 'Create a web client before adding redirect URIs.',
                    'actionHref' => route('portal.developer.sites.credentials.index', $site),
                    'actionLabel' => 'Open credentials',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
