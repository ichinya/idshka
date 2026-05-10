@php
    $activeWorkspace = $activeWorkspace ?? 'account';
    $items = [
        ['key' => 'account', 'label' => 'Account', 'href' => route('portal.account.overview')],
        ['key' => 'developer', 'label' => 'Developer', 'href' => route('portal.developer.overview')],
        ['key' => 'audit', 'label' => 'Audit', 'href' => route('portal.audit.index')],
    ];
@endphp

<aside class="border-b border-zinc-200 bg-white lg:min-h-screen lg:border-b-0 lg:border-r">
    <div class="flex items-center justify-between gap-3 px-4 py-4 lg:block lg:px-5">
        <a class="text-lg font-semibold tracking-normal text-zinc-950" href="{{ route('portal.account.overview') }}">idshka</a>
        <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium lg:hidden" type="button" data-portal-nav-toggle>
            Menu
        </button>
    </div>

    <nav class="hidden px-3 pb-4 lg:block" data-portal-nav>
        <div class="grid gap-1">
            @foreach ($items as $item)
                <a
                    class="rounded-md px-3 py-2 text-sm font-medium {{ $activeWorkspace === $item['key'] ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-950' }}"
                    href="{{ $item['href'] }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</aside>
