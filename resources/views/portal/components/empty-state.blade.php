<div class="rounded-md border border-dashed border-zinc-300 bg-zinc-50 px-4 py-5 text-sm text-zinc-700">
    <p class="font-medium text-zinc-900">{{ $title }}</p>
    @isset($text)
        <p class="mt-1 leading-6">{{ $text }}</p>
    @endisset
    @isset($actionHref)
        <a class="mt-3 inline-flex rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800" href="{{ $actionHref }}">
            {{ $actionLabel ?? 'Open' }}
        </a>
    @endisset
</div>
