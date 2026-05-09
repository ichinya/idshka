@extends('layouts.portal', ['activeWorkspace' => 'developer'])

@section('title', 'Domain verification')

@section('content')
    @include('portal.components.page-header', [
        'title' => 'Domain verification',
        'subtitle' => $site->normalized_domain,
    ])

    @php
        $dns = $site->verifications->where('method', 'dns_txt')->sortByDesc('id')->first();
        $file = $site->verifications->where('method', 'file')->sortByDesc('id')->first();
    @endphp

    <section class="grid gap-4 md:grid-cols-2">
        @component('portal.components.card', ['title' => 'DNS TXT'])
            <dl class="grid gap-2 text-sm">
                <div>
                    <dt class="text-zinc-600">Host</dt>
                    <dd class="break-all font-mono text-xs">{{ '_idshka.'.$site->normalized_domain }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-600">Value</dt>
                    <dd class="break-all font-mono text-xs">idshka-site-verification={{ $dns?->token }}</dd>
                </div>
            </dl>
            <form class="mt-4" method="POST" action="{{ route('portal.sites.verify', $site) }}">
                @csrf
                <input type="hidden" name="method" value="dns_txt">
                <button class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white" type="submit">Check DNS</button>
            </form>
        @endcomponent

        @component('portal.components.card', ['title' => 'Well-known file'])
            <dl class="grid gap-2 text-sm">
                <div>
                    <dt class="text-zinc-600">URL</dt>
                    <dd class="break-all font-mono text-xs">https://{{ $site->normalized_domain }}/.well-known/idshka-site-verification.txt</dd>
                </div>
                <div>
                    <dt class="text-zinc-600">Content</dt>
                    <dd class="break-all font-mono text-xs">{{ $file?->token }}</dd>
                </div>
            </dl>
            <form class="mt-4" method="POST" action="{{ route('portal.sites.verify', $site) }}">
                @csrf
                <input type="hidden" name="method" value="file">
                <button class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white" type="submit">Check file</button>
            </form>
        @endcomponent
    </section>
@endsection
