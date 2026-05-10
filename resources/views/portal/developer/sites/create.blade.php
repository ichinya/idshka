@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Add site')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Add site',
        'subtitle' => 'Connect a domain that will use idshka.ru for API-only access, Web Login, or both.',
    ])

    @component('portal.components.card', ['title' => 'New site'])
        <form class="grid gap-3" method="POST" action="{{ route('portal.sites.store') }}">
            @csrf
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Domain</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" name="domain" value="{{ old('domain') }}" placeholder="https://example.test" required>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Display name</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" name="display_name" value="{{ old('display_name') }}" placeholder="Example App">
            </label>
            <button class="w-fit rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800" type="submit">
                Create site
            </button>
        </form>
    @endcomponent
@endsection
