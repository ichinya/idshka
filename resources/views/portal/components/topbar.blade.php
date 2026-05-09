<header class="border-b border-zinc-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-zinc-600">{{ auth()->user()?->email }}</p>
        </div>
        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium hover:bg-zinc-100" type="submit">
                Logout
            </button>
        </form>
    </div>
</header>
