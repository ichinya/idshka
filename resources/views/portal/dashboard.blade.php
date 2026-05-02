@php
    use App\Domain\Sites\Enums\SiteModeType;
    use App\Domain\Sites\Enums\SiteVerificationStatus;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Portal · {{ config('app.name', 'idshka') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-zinc-50 text-zinc-950 antialiased">
        <main class="mx-auto flex min-h-screen max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 border-b border-zinc-200 pb-5 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-medium text-cyan-700">idshka control plane</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-normal">Мои сайты</h1>
                </div>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium hover:bg-zinc-100" type="submit">
                        Logout
                    </button>
                </form>
            </header>

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

            @if (session('issued_api_token'))
                <section class="rounded-md border border-amber-300 bg-amber-50 p-4">
                    <h2 class="text-base font-semibold">Токен создан</h2>
                    <dl class="mt-3 grid gap-2 text-sm md:grid-cols-2">
                        <div>
                            <dt class="font-medium text-zinc-600">Token ID</dt>
                            <dd>{{ session('issued_api_token.token_id') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-600">Expires</dt>
                            <dd>{{ session('issued_api_token.expires_at') }}</dd>
                        </div>
                    </dl>
                    <pre class="mt-3 overflow-x-auto rounded-md bg-zinc-950 p-3 text-xs text-zinc-50">{{ session('issued_api_token.token') }}</pre>
                </section>
            @endif

            @if (session('issued_client'))
                <section class="rounded-md border border-amber-300 bg-amber-50 p-4">
                    <h2 class="text-base font-semibold">Client secret</h2>
                    <dl class="mt-3 grid gap-2 text-sm md:grid-cols-2">
                        <div>
                            <dt class="font-medium text-zinc-600">Client ID</dt>
                            <dd>{{ session('issued_client.client_id') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-600">Client secret</dt>
                            <dd class="font-mono text-xs">{{ session('issued_client.client_secret') }}</dd>
                        </div>
                    </dl>
                </section>
            @endif

            <section class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
                <div class="rounded-md border border-zinc-200 bg-white">
                    <div class="border-b border-zinc-200 px-4 py-3">
                        <h2 class="text-lg font-semibold">Мои сайты</h2>
                    </div>
                    <div class="divide-y divide-zinc-200">
                        @forelse ($sites as $site)
                            @php
                                $modeValues = $site->modes->pluck('mode')->all();
                                $dns = $site->verifications->where('method', 'dns_txt')->sortByDesc('id')->first();
                                $file = $site->verifications->where('method', 'file')->sortByDesc('id')->first();
                            @endphp
                            <article class="p-4">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h3 class="font-semibold">{{ $site->display_name ?: $site->normalized_domain }}</h3>
                                        <p class="text-sm text-zinc-600">{{ $site->normalized_domain }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            <span class="rounded-sm bg-zinc-100 px-2 py-1">{{ $site->verification_status }}</span>
                                            @foreach ($modeValues as $mode)
                                                <span class="rounded-sm bg-cyan-50 px-2 py-1 text-cyan-900">{{ $mode }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('portal.sites.modes.store', [$site, SiteModeType::ApiResource->value]) }}">
                                            @csrf
                                            <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" type="submit">
                                                API resource
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('portal.sites.modes.store', [$site, SiteModeType::WebClient->value]) }}">
                                            @csrf
                                            <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" type="submit">
                                                Web client
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                @if ($site->verification_status !== SiteVerificationStatus::Verified->value)
                                    <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                                        <div class="rounded-md border border-zinc-200 p-3">
                                            <p class="font-medium">DNS TXT</p>
                                            <p class="mt-1 font-mono text-xs">{{ '_idshka.'.$site->normalized_domain }}</p>
                                            <p class="mt-1 break-all font-mono text-xs">idshka-site-verification={{ $dns?->token }}</p>
                                            <form class="mt-3" method="POST" action="{{ route('portal.sites.verify', $site) }}">
                                                @csrf
                                                <input type="hidden" name="method" value="dns_txt">
                                                <button class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white" type="submit">Check DNS</button>
                                            </form>
                                        </div>
                                        <div class="rounded-md border border-zinc-200 p-3">
                                            <p class="font-medium">File</p>
                                            <p class="mt-1 break-all font-mono text-xs">https://{{ $site->normalized_domain }}/.well-known/idshka-site-verification.txt</p>
                                            <p class="mt-1 break-all font-mono text-xs">{{ $file?->token }}</p>
                                            <form class="mt-3" method="POST" action="{{ route('portal.sites.verify', $site) }}">
                                                @csrf
                                                <input type="hidden" name="method" value="file">
                                                <button class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white" type="submit">Check file</button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </article>
                        @empty
                            <p class="p-4 text-sm text-zinc-600">No sites.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-md border border-zinc-200 bg-white p-4">
                    <h2 class="text-base font-semibold">New site</h2>
                    <form class="mt-4 grid gap-3" method="POST" action="{{ route('portal.sites.store') }}">
                        @csrf
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Domain</span>
                            <input class="rounded-md border border-zinc-300 px-3 py-2" name="domain" value="{{ old('domain') }}" required>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Display name</span>
                            <input class="rounded-md border border-zinc-300 px-3 py-2" name="display_name" value="{{ old('display_name') }}">
                        </label>
                        <button class="rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800" type="submit">
                            Create site
                        </button>
                    </form>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-md border border-zinc-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">API tokens</h2>
                    </div>
                    <form class="mt-4 grid gap-3 md:grid-cols-2" method="POST" action="{{ route('portal.api-tokens.store') }}">
                        @csrf
                        <label class="grid gap-1 text-sm md:col-span-2">
                            <span class="font-medium">Site</span>
                            <select class="rounded-md border border-zinc-300 px-3 py-2" name="site_id" required>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->normalized_domain }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Scopes</span>
                            <input class="rounded-md border border-zinc-300 px-3 py-2" name="scopes" value="orders.read">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Permissions</span>
                            <input class="rounded-md border border-zinc-300 px-3 py-2" name="permissions" value="orders.read">
                        </label>
                        <button class="rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800 md:col-span-2" type="submit">
                            Issue token
                        </button>
                    </form>

                    <div class="mt-5 divide-y divide-zinc-200">
                        @forelse ($apiTokens as $token)
                            <div class="grid gap-3 py-3 text-sm md:grid-cols-[1fr_auto]">
                                <div>
                                    <p class="font-medium">{{ $token->site?->normalized_domain }} · {{ $token->audience }}</p>
                                    <p class="font-mono text-xs text-zinc-600">{{ $token->jti }}</p>
                                    <p class="text-xs text-zinc-600">Expires {{ $token->expires_at?->toISOString() }} · {{ $token->revoked_at ? 'revoked' : 'active' }}</p>
                                </div>
                                <form class="flex gap-2" method="POST" action="{{ route('portal.api-tokens.revoke', $token) }}">
                                    @csrf
                                    <input class="w-24 rounded-md border border-zinc-300 px-2 py-1 text-xs" name="confirm" placeholder="revoke">
                                    <button class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50" type="submit">
                                        Revoke
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-zinc-600">No API tokens.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-md border border-zinc-200 bg-white p-4">
                    @php
                        $defaultWebClientSite = $sites->first();
                        $defaultRedirectUri = $defaultWebClientSite
                            ? 'https://'.$defaultWebClientSite->normalized_domain.'/auth/idshka/callback'
                            : '';
                    @endphp
                    <h2 class="text-lg font-semibold">Web clients</h2>
                    <form class="mt-4 grid gap-3" method="POST" action="{{ route('portal.clients.store') }}">
                        @csrf
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Site</span>
                            <select class="rounded-md border border-zinc-300 px-3 py-2" name="site_id" required data-web-client-site-select>
                                @foreach ($sites as $site)
                                    @php
                                        $siteDefaultRedirectUri = 'https://'.$site->normalized_domain.'/auth/idshka/callback';
                                    @endphp
                                    <option value="{{ $site->id }}" data-default-redirect-uri="{{ $siteDefaultRedirectUri }}">{{ $site->normalized_domain }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Name</span>
                            <input class="rounded-md border border-zinc-300 px-3 py-2" name="name" value="Example Web" required>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Redirect URI</span>
                            <input class="rounded-md border border-zinc-300 px-3 py-2" name="redirect_uri" value="{{ $defaultRedirectUri }}" placeholder="{{ $defaultRedirectUri }}" required data-autofilled="1" data-web-client-redirect-uri>
                        </label>
                        <button class="rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800" type="submit">
                            Create client
                        </button>
                    </form>
                    <script>
                        (() => {
                            const select = document.querySelector('[data-web-client-site-select]');
                            const input = document.querySelector('[data-web-client-redirect-uri]');

                            if (!select || !input) {
                                return;
                            }

                            const syncRedirectUri = () => {
                                const uri = select.selectedOptions[0]?.dataset.defaultRedirectUri || '';

                                input.placeholder = uri;

                                if (input.dataset.autofilled === '1') {
                                    input.value = uri;
                                }
                            };

                            input.addEventListener('input', () => {
                                input.dataset.autofilled = '0';
                            });

                            select.addEventListener('change', syncRedirectUri);
                            syncRedirectUri();
                        })();
                    </script>

                    <div class="mt-5 divide-y divide-zinc-200">
                        @forelse ($clients as $client)
                            <div class="py-3 text-sm">
                                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="font-medium">{{ $client->name }} · {{ $client->client_id }}</p>
                                        <p class="text-xs text-zinc-600">{{ $client->site?->normalized_domain }} · {{ $client->revoked_at ? 'revoked' : 'active' }}</p>
                                    </div>
                                    <form class="flex gap-2" method="POST" action="{{ route('portal.clients.revoke', $client) }}">
                                        @csrf
                                        <input class="w-24 rounded-md border border-zinc-300 px-2 py-1 text-xs" name="confirm" placeholder="revoke">
                                        <button class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50" type="submit">
                                            Revoke
                                        </button>
                                    </form>
                                </div>
                                <ul class="mt-3 grid gap-1 text-xs text-zinc-700">
                                    @foreach ($client->redirectUris as $redirectUri)
                                        <li class="break-all font-mono">{{ $redirectUri->redirect_uri }}</li>
                                    @endforeach
                                </ul>
                                <form class="mt-3 flex flex-col gap-2 md:flex-row" method="POST" action="{{ route('portal.clients.redirect-uris.store', $client) }}">
                                    @csrf
                                    <input class="min-w-0 flex-1 rounded-md border border-zinc-300 px-3 py-2 text-sm" name="redirect_uri" placeholder="{{ $client->site?->normalized_domain ? 'https://'.$client->site->normalized_domain.'/auth/idshka/callback' : '' }}">
                                    <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" type="submit">
                                        Add redirect URI
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-zinc-600">No web clients.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="rounded-md border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-4 py-3">
                    <h2 class="text-lg font-semibold">Audit</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase text-zinc-600">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Time</th>
                                <th class="px-4 py-3 font-semibold">Action</th>
                                <th class="px-4 py-3 font-semibold">Summary</th>
                                <th class="px-4 py-3 font-semibold">Site</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @forelse ($auditEvents as $event)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $event->occurred_at?->toISOString() }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $event->action }}</td>
                                    <td class="px-4 py-3">{{ $event->summary }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $event->site_id }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-3 text-zinc-600" colspan="4">No audit events.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </body>
</html>
