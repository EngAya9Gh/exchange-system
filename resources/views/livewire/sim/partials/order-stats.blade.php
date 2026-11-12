{{--
    Stat cards above «طلبات الباقات», for the chosen period / search.
    Expects $stats (Orders::statistics), $topPackages, $canManage.
    Profit and supplier cost are shown to the team only.
--}}
@php
    $money = fn (float $amount) => \App\Models\SimPackage::formatPrice(round($amount, 2)) . ' ₺';

    $ordersSub = __('messages.sim_stat_orders_sub', ['done' => $stats['done_count'], 'refunded' => $stats['refunded_count']]);
    if ($stats['open_count'] > 0) {
        $ordersSub .= ' · ' . __('messages.sim_stat_open', ['count' => $stats['open_count']]);
    }

    $cards = [[
        'label' => __('messages.sim_stat_orders'),
        'value' => number_format($stats['total']),
        'sub' => $ordersSub,
        'tone' => 'bg-indigo-50 text-indigo-600',
        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'valueClass' => 'text-slate-900',
    ], [
        'label' => $canManage ? __('messages.sim_stat_sales') : __('messages.sim_stat_spent'),
        'value' => $money($stats['sales']),
        'sub' => __('messages.sim_stat_average', ['amount' => $money(round($stats['average']))]),
        'tone' => 'bg-sky-50 text-sky-600',
        'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'valueClass' => 'text-slate-900',
    ]];

    if ($canManage) {
        $isLoss = $stats['profit'] < 0;
        $cards[] = [
            'label' => $isLoss ? __('messages.sim_stat_loss') : __('messages.sim_stat_profit'),
            'value' => $stats['costed_count'] > 0 ? $money(abs($stats['profit'])) : '—',
            'sub' => $stats['costed_count'] > 0
                ? __('messages.sim_stat_profit_sub', ['sales' => $money($stats['costed_sales']), 'cost' => $money($stats['costed_cost']), 'count' => $stats['costed_count']])
                : __('messages.sim_stat_profit_none'),
            'tone' => $isLoss ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600',
            'icon' => $isLoss ? 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' : 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'valueClass' => $stats['costed_count'] === 0 ? 'text-slate-400' : ($isLoss ? 'text-rose-600' : 'text-emerald-600'),
        ];
    }

    $cards[] = [
        'label' => __('messages.sim_stat_refunded'),
        'value' => $money($stats['refunded']),
        'sub' => __('messages.sim_stat_refunded_sub', ['count' => $stats['refunded_count']]),
        'tone' => 'bg-amber-50 text-amber-600',
        'icon' => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6',
        'valueClass' => 'text-slate-900',
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 {{ $canManage ? 'xl:grid-cols-4' : 'lg:grid-cols-3' }} gap-3 mb-4">
    @foreach($cards as $card)
        <div class="rounded-2xl border border-slate-100 bg-gradient-to-br from-white to-slate-50/80 p-4 flex items-start gap-3">
            <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center {{ $card['tone'] }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"></path></svg>
            </div>
            <div class="min-w-0">
                <div class="text-xs font-bold text-slate-500">{{ $card['label'] }}</div>
                <div class="text-xl sm:text-2xl font-black leading-tight mt-0.5 {{ $card['valueClass'] }}"><span dir="ltr">{{ $card['value'] }}</span></div>
                <div class="text-[11px] text-slate-400 mt-1 leading-relaxed">{{ $card['sub'] }}</div>
            </div>
        </div>
    @endforeach
</div>

@if($canManage && $topPackages->isNotEmpty())
    <div class="flex flex-wrap items-center gap-2 mb-6 text-xs">
        <span class="font-bold text-slate-500">{{ __('messages.sim_stat_top_packages') }}:</span>
        @foreach($topPackages as $index => $top)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 font-bold text-slate-700">
                <span class="w-5 h-5 rounded-full bg-white text-[10px] font-black text-slate-500 flex items-center justify-center">{{ $index + 1 }}</span>
                {{ $top->name }}
                <span class="text-slate-400 font-semibold">· {{ __('messages.sim_stat_orders_count', ['count' => $top->orders_count]) }}</span>
            </span>
        @endforeach
    </div>
@else
    <div class="mb-6"></div>
@endif
