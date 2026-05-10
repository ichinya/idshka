<section class="rounded-md border border-zinc-200 bg-white p-4">
    @isset($title)
        <div class="mb-3">
            <h2 class="text-base font-semibold">{{ $title }}</h2>
            @isset($subtitle)
                <p class="mt-1 text-sm text-zinc-600">{{ $subtitle }}</p>
            @endisset
        </div>
    @endisset
    {{ $slot }}
</section>
