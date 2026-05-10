@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Gateway guide')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'API-only gateway guide',
        'subtitle' => $site->normalized_domain,
    ])

    @component('portal.components.warning-callout', ['title' => 'Header trust boundary'])
        Upstream applications must not trust client-supplied X-Idshka-* headers. The gateway removes spoofed headers and injects trusted context after JWT validation.
    @endcomponent

    @component('portal.components.card', ['title' => 'Public endpoints'])
        <dl class="grid gap-2 text-sm">
            <div>
                <dt class="font-medium text-zinc-600">JWKS</dt>
                <dd class="font-mono text-xs">{{ url('/oauth/jwks.json') }}</dd>
            </div>
            <div>
                <dt class="font-medium text-zinc-600">Audience</dt>
                <dd class="font-mono text-xs">{{ $site->normalized_domain }}</dd>
            </div>
        </dl>
    @endcomponent

    @component('portal.components.code-snippet', ['id' => 'gateway-authorization', 'label' => 'Authorization header'])
Authorization: Bearer &lt;idshka-user-api-token&gt;
    @endcomponent

    @component('portal.components.code-snippet', ['id' => 'gateway-headers', 'label' => 'Trusted upstream headers'])
X-Idshka-Authenticated: 1
X-Idshka-User-Id: &lt;user-id&gt;
X-Idshka-Site-Id: {{ $site->id }}
X-Idshka-Audience: {{ $site->normalized_domain }}
X-Idshka-Scopes: orders.read
X-Idshka-Permissions: orders.read
X-Idshka-JTI: &lt;token-jti&gt;
X-Idshka-Token-Exp: &lt;unix-timestamp&gt;
    @endcomponent
@endsection
