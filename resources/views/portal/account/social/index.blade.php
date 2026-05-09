@extends('layouts.portal', ['activeWorkspace' => 'account'])

@section('title', 'Social accounts')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Social accounts',
        'subtitle' => 'Link supported external providers for faster first-party sign in.',
    ])

    <section class="grid gap-4 md:grid-cols-3">
        @foreach ($providers as $provider)
            @php($account = $linkedAccounts->get($provider->value))
            @component('portal.components.card', ['title' => $provider->label()])
                @if ($account)
                    <div class="grid gap-2 text-sm">
                        @include('portal.components.status-badge', ['variant' => 'active', 'label' => 'Linked'])
                        <p class="break-words text-zinc-700">{{ $account->email ?: $account->name ?: $account->provider_user_id }}</p>
                        <form method="POST" action="{{ route('auth.social.unlink', $provider->value) }}">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50" type="submit">
                                Unlink
                            </button>
                        </form>
                    </div>
                @else
                    <div class="grid gap-3 text-sm">
                        @include('portal.components.status-badge', ['variant' => 'neutral', 'label' => 'Not linked'])
                        <a class="inline-flex w-fit rounded-md bg-zinc-900 px-3 py-2 font-semibold text-white hover:bg-zinc-800" href="{{ route('auth.social.link.redirect', $provider->value) }}">
                            Link {{ $provider->label() }}
                        </a>
                    </div>
                @endif
            @endcomponent
        @endforeach
    </section>
@endsection
