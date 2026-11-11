{{-- Colored package label. Expects $badge (one of SimPackage::BADGES); optional $size classes. --}}
@php
    $badgeStyles = [
        'best_seller' => ['from-orange-500 to-amber-400', '🔥'],
        'best_value' => ['from-emerald-500 to-teal-400', '💎'],
        'new' => ['from-sky-500 to-cyan-400', '✨'],
        'offer' => ['from-fuchsia-600 to-pink-500', '🎁'],
        'limited' => ['from-violet-600 to-indigo-500', '⏰'],
    ];
    $badgeStyle = $badgeStyles[$badge ?? ''] ?? null;
@endphp
@if($badgeStyle)
    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-gradient-to-r {{ $badgeStyle[0] }} text-white font-black shadow-md shadow-black/10 ring-1 ring-white/40 {{ $size ?? 'text-[11px] px-2.5 py-1' }}">
        <span aria-hidden="true">{{ $badgeStyle[1] }}</span>{{ __('messages.sim_badge_' . $badge) }}
    </span>
@endif
