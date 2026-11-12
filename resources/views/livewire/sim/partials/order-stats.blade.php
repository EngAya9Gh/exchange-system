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
    @php
        // Gold / silver / bronze
        $medals = [
            ['card' => 'from-amber-50 via-white to-white ring-amber-200', 'badge' => 'from-amber-400 to-yellow-300 text-amber-900 shadow-amber-200', 'bar' => 'from-amber-400 to-yellow-300'],
            ['card' => 'from-slate-100 via-white to-white ring-slate-200', 'badge' => 'from-slate-400 to-slate-300 text-white shadow-slate-200', 'bar' => 'from-slate-400 to-slate-300'],
            ['card' => 'from-orange-50 via-white to-white ring-orange-200', 'badge' => 'from-orange-500 to-amber-600 text-white shadow-orange-200', 'bar' => 'from-orange-500 to-amber-500'],
        ];
    @endphp
    <div class="rounded-2xl border border-slate-100 bg-white p-4 sm:p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h4 class="text-sm font-black text-slate-800 flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center">
                    <svg class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2H6v2H2v3a5 5 0 004.2 4.94A6.01 6.01 0 0011 15.9V18H8v2h8v-2h-3v-2.1a6.01 6.01 0 004.8-3.96A5 5 0 0022 7V4h-4V2zM4 7V6h2v3.83A3 3 0 014 7zm16 0a3 3 0 01-2 2.83V6h2v1z"/></svg>
                </span>
                {{ __('messages.sim_stat_top_packages') }}
            </h4>
            <span class="text-[11px] font-semibold text-slate-400">{{ __('messages.sim_stat_top_hint') }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($topPackages as $index => $top)
                @php
                    $medal = $medals[$index] ?? $medals[2];
                    $package = $top->package;
                    $flag = $package?->region === \App\Models\SimPackage::REGION_EUROPE ? '🇪🇺' : '🇹🇷';
                @endphp
                <div class="rounded-xl bg-gradient-to-br ring-1 p-4 {{ $medal['card'] }}">
                    <div class="flex items-start gap-3">
                        <span class="w-9 h-9 shrink-0 rounded-full bg-gradient-to-br shadow-md font-black text-sm flex items-center justify-center {{ $medal['badge'] }}">{{ $index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            @if($package)
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span aria-hidden="true">{{ $flag }}</span>
                                    <span class="text-base font-black text-slate-900 leading-tight" dir="ltr">{{ $package->data_gb }} GB</span>
                                    <span class="rounded-full bg-white ring-1 ring-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $package->durationLabel() }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1 truncate" title="{{ $top->name }}">{{ $top->name }}</div>
                            @else
                                <div class="text-sm font-black text-slate-900 leading-snug">{{ $top->name }}</div>
                            @endif
                        </div>
                        <span class="shrink-0 text-lg font-black text-slate-800" dir="ltr">{{ $top->share }}%</span>
                    </div>

                    <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden" role="presentation">
                        <div class="h-full rounded-full bg-gradient-to-r {{ $medal['bar'] }}" style="width: {{ max(4, min(100, $top->share)) }}%"></div>
                    </div>

                    <div class="mt-2.5 flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-600">{{ __('messages.sim_stat_orders_count', ['count' => $top->orders_count]) }}</span>
                        <span class="font-black text-slate-800" dir="ltr">{{ $money($top->sales) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="h-2"></div>
@endif
