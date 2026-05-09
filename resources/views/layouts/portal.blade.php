<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Portal') · {{ config('app.name', 'idshka') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-zinc-50 text-zinc-950 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
            @include('portal.components.sidebar', ['activeWorkspace' => $activeWorkspace ?? 'account'])

            <div class="min-w-0">
                @include('portal.components.topbar')

                <main class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('portal_notice'))
                        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                            {{ session('portal_notice') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-950">
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
