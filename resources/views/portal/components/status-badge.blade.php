@php
    $variant = $variant ?? 'neutral';
    $classes = match ($variant) {
        'success', 'verified', 'active' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'warning', 'pending', 'unverified' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger', 'revoked', 'expired' => 'border-red-200 bg-red-50 text-red-800',
        'info', 'api-only', 'web-login', 'both' => 'border-cyan-200 bg-cyan-50 text-cyan-900',
        default => 'border-zinc-200 bg-zinc-100 text-zinc-700',
    };
@endphp

<span class="inline-flex items-center rounded-sm border px-2 py-1 text-xs font-medium {{ $classes }}">
    {{ $slot ?? $label ?? $variant }}
</span>
