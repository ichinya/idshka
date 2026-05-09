@extends('layouts.portal', ['activeWorkspace' => 'audit'])

@section('title', 'Audit')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Audit',
        'subtitle' => 'Searchable security and product event history scoped to your user and owned sites.',
    ])

    @component('portal.components.card', ['title' => 'Filters'])
        <form class="grid gap-3 md:grid-cols-3 xl:grid-cols-6" method="GET" action="{{ route('portal.audit.index') }}">
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Category</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" name="category" value="{{ $filters['category'] ?? '' }}" placeholder="site">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Action</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" name="action" value="{{ $filters['action'] ?? '' }}" placeholder="site.connected">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Site</span>
                <select class="rounded-md border border-zinc-300 px-3 py-2" name="site_id">
                    <option value="">Any</option>
                    @foreach ($ownedSites as $site)
                        <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') === $site->id)>{{ $site->normalized_domain }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Actor</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" name="actor" value="{{ $filters['actor'] ?? '' }}" placeholder="{{ auth()->id() }}">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">From</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">To</span>
                <input class="rounded-md border border-zinc-300 px-3 py-2" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </label>
            <button class="w-fit rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 md:col-span-3 xl:col-span-6" type="submit">
                Apply filters
            </button>
        </form>
    @endcomponent

    @component('portal.components.card', ['title' => 'Events'])
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-600">
                    <tr>
                        <th class="px-3 py-3 font-semibold">Time</th>
                        <th class="px-3 py-3 font-semibold">Action</th>
                        <th class="px-3 py-3 font-semibold">Summary</th>
                        <th class="px-3 py-3 font-semibold">Site</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse ($events as $event)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-3 text-zinc-600">{{ $event->occurred_at?->toISOString() }}</td>
                            <td class="whitespace-nowrap px-3 py-3">
                                <a class="font-mono text-xs text-cyan-700 hover:text-cyan-900" href="{{ route('portal.audit.show', $event) }}">{{ $event->action }}</a>
                            </td>
                            <td class="px-3 py-3">{{ $event->summary }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-zinc-600">{{ $event->site_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-3">
                                @include('portal.components.empty-state', [
                                    'title' => 'No audit events found',
                                    'text' => 'Try a different filter or period. New user, token, and site events appear here automatically.',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
@endsection
