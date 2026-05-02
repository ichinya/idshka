<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>IDShka sign in</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-5xl flex-col justify-center gap-8 px-6 py-10">
            <header class="max-w-2xl">
                <a href="{{ url('/') }}" class="text-sm font-semibold uppercase tracking-wider text-emerald-300">idshka.ru</a>
                <h1 class="mt-4 text-3xl font-semibold tracking-normal text-white md:text-4xl">IDShka sign in</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-zinc-300">
                    Manage connected sites, API tokens, OIDC clients, and audit events from the portal.
                </p>
            </header>

            @if ($errors->any())
                <section class="rounded-md border border-red-400/40 bg-red-950/40 p-4 text-sm text-red-100">
                    <p class="font-semibold">Check the form fields and try again.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="grid gap-5 md:grid-cols-2">
                <form method="POST" action="{{ route('auth.login') }}" class="rounded-lg border border-zinc-800 bg-zinc-900 p-5 shadow-sm">
                    @csrf
                    <h2 class="text-lg font-semibold text-white">Log in</h2>
                    <div class="mt-5 grid gap-4">
                        <label class="grid gap-2 text-sm text-zinc-300">
                            Email
                            <input class="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none focus:border-emerald-400" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                        </label>
                        <label class="grid gap-2 text-sm text-zinc-300">
                            Password
                            <input class="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none focus:border-emerald-400" name="password" type="password" autocomplete="current-password" required>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-zinc-300">
                            <input class="size-4 rounded border-zinc-700 bg-zinc-950 text-emerald-500" name="remember" type="checkbox" value="1">
                            Remember me
                        </label>
                        <button class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 hover:bg-emerald-300" type="submit">Log in</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('auth.register') }}" class="rounded-lg border border-zinc-800 bg-zinc-900 p-5 shadow-sm">
                    @csrf
                    <h2 class="text-lg font-semibold text-white">Create account</h2>
                    <div class="mt-5 grid gap-4">
                        <label class="grid gap-2 text-sm text-zinc-300">
                            Name
                            <input class="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none focus:border-emerald-400" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required>
                        </label>
                        <label class="grid gap-2 text-sm text-zinc-300">
                            Email
                            <input class="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none focus:border-emerald-400" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                        </label>
                        <label class="grid gap-2 text-sm text-zinc-300">
                            Password
                            <input class="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none focus:border-emerald-400" name="password" type="password" autocomplete="new-password" required>
                        </label>
                        <label class="grid gap-2 text-sm text-zinc-300">
                            Confirm password
                            <input class="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none focus:border-emerald-400" name="password_confirmation" type="password" autocomplete="new-password" required>
                        </label>
                        <button class="rounded-md border border-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-200 hover:bg-emerald-400 hover:text-zinc-950" type="submit">Create account</button>
                    </div>
                </form>
            </section>

            <section class="flex flex-wrap gap-3 border-t border-zinc-800 pt-5">
                @foreach (['google' => 'Google', 'vk' => 'VK', 'yandex' => 'Yandex'] as $provider => $label)
                    <a class="rounded-md border border-zinc-700 px-4 py-2 text-sm text-zinc-200 hover:border-emerald-400 hover:text-emerald-200" href="{{ route('auth.social.redirect', $provider) }}">
                        Continue with {{ $label }}
                    </a>
                @endforeach
            </section>
        </main>
    </body>
</html>
