@extends('layouts.portal', ['activeWorkspace' => 'audit'])

@section('title', 'Audit event')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Audit event',
        'subtitle' => $event->action,
    ])

    @component('portal.components.card', ['title' => 'Event details'])
        <dl class="grid gap-3 text-sm md:grid-cols-2">
            <div>
                <dt class="text-zinc-600">Time</dt>
                <dd class="font-medium">{{ $event->occurred_at?->toISOString() }}</dd>
            </div>
            <div>
                <dt class="text-zinc-600">Category</dt>
                <dd class="font-medium">{{ $event->category }}</dd>
            </div>
            <div>
                <dt class="text-zinc-600">Action</dt>
                <dd class="font-mono text-xs">{{ $event->action }}</dd>
            </div>
            <div>
                <dt class="text-zinc-600">Site</dt>
                <dd class="font-mono text-xs">{{ $event->site_id ?? 'none' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-zinc-600">Summary</dt>
                <dd class="font-medium">{{ $event->summary }}</dd>
            </div>
        </dl>
    @endcomponent

    @component('portal.components.card', ['title' => 'Metadata'])
        <pre class="overflow-x-auto rounded-md bg-zinc-950 p-3 text-xs leading-6 text-zinc-50">{{ $metadataJson }}</pre>
    @endcomponent
@endsection
