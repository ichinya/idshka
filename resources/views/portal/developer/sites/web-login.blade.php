@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Web Login guide')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Web Login guide',
        'subtitle' => $site->normalized_domain,
    ])

    @component('portal.components.card', ['title' => 'Flow'])
        <ol class="list-inside list-decimal space-y-2 text-sm leading-6 text-zinc-700">
            <li>Create a web client and exact redirect URI.</li>
            <li>Redirect the browser to <span class="font-mono text-xs">/oauth/authorize</span> with state, nonce, and S256 PKCE.</li>
            <li>Exchange the authorization code at <span class="font-mono text-xs">/oauth/token</span>.</li>
            <li>Validate the ID token with <span class="font-mono text-xs">/oauth/jwks.json</span>.</li>
            <li>Use <span class="font-mono text-xs">/oauth/userinfo</span> for granted profile/email claims, then create a local session.</li>
        </ol>
    @endcomponent

    @component('portal.components.code-snippet', ['id' => 'oauth-authorize', 'label' => 'Authorize endpoint'])
GET {{ url('/oauth/authorize') }}?response_type=code&amp;client_id=&lt;client_id&gt;&amp;redirect_uri=https://{{ $site->normalized_domain }}/auth/idshka/callback&amp;scope=openid%20profile%20email&amp;state=&lt;state&gt;&amp;nonce=&lt;nonce&gt;&amp;code_challenge=&lt;s256-challenge&gt;&amp;code_challenge_method=S256
    @endcomponent

    @component('portal.components.code-snippet', ['id' => 'oauth-token', 'label' => 'Token endpoint'])
POST {{ url('/oauth/token') }}
grant_type=authorization_code
client_id=&lt;client_id&gt;
client_secret=&lt;client_secret&gt;
code=&lt;authorization_code&gt;
redirect_uri=https://{{ $site->normalized_domain }}/auth/idshka/callback
code_verifier=&lt;pkce-verifier&gt;
    @endcomponent
@endsection
