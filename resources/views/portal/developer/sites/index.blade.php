@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Sites')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Sites',
        'subtitle' => 'Owned connected sites and their mode/verification state.',
        'actions' => '<a class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800" href="'.route('portal.developer.sites.create').'">Add site</a>',
    ])

    @component('portal.components.card', ['title' => 'Connected sites'])
        <div class="divide-y divide-zinc-200">
            @forelse ($sites as $site)
                <div class="grid gap-3 py-4 md:grid-cols-[1fr_auto]">
                    <div>
                        <h2 class="font-semibold">{{ $site->display_name ?: $site->normalized_domain }}</h2>
                        <p class="text-sm text-zinc-600">{{ $site->normalized_domain }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @include('portal.components.status-badge', ['variant' => $site->isVerified() ? 'verified' : 'unverified', 'label' => $site->verification_status])
                            @foreach ($site->modes as $mode)
                                @include('portal.components.status-badge', ['variant' => 'info', 'label' => $mode->mode])
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 md:justify-end">
                        <a class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" href="{{ route('portal.developer.sites.show', $site) }}">Open</a>
                        <a class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" href="{{ route('portal.developer.sites.verification.show', $site) }}">Verify</a>
                    </div>
                </div>
            @empty
                @include('portal.components.empty-state', [
                    'title' => 'No connected sites',
                    'text' => 'Add your first site and verify ownership with DNS TXT or a well-known file.',
                    'actionHref' => route('portal.developer.sites.create'),
                    'actionLabel' => 'Add site',
                ])
            @endforelse
        </div>
    @endcomponent
@endsection
