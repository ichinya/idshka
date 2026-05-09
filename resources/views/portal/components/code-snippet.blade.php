<div class="rounded-md border border-zinc-800 bg-zinc-950">
    <div class="flex items-center justify-between gap-3 border-b border-zinc-800 px-3 py-2">
        <p class="text-xs font-medium text-zinc-300">{{ $label ?? 'Snippet' }}</p>
        @include('portal.components.copy-button', ['target' => $id])
    </div>
    <pre id="{{ $id }}" class="overflow-x-auto p-3 text-xs leading-6 text-zinc-50"><code>{{ $slot }}</code></pre>
</div>
