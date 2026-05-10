<header class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
    <div>
        <p class="text-sm font-medium text-cyan-700">{{ $eyebrow ?? 'idshka portal' }}</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-normal">{{ $title }}</h1>
        @isset($subtitle)
            <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-600">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{!! $actions !!}</div>
    @endisset
</header>
