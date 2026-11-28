{{--
    Package categories as cards (instead of tabs), on «شراء باقة» and «إدارة الباقات والأسعار».
    Expects: $tabs (SimPackage::visibleTabs), $current (open category), $action (Livewire method taking the key),
             $packagesByTab (packages grouped by tabKey()), $mode ('buy' | 'admin').
    Each category has its own color, the same family as its package cards (partials/package-card).
--}}
@php
    $categoryThemes = [
        'offers' => ['icon' => 'from-pink-500 to-rose-600', 'on' => 'border-pink-400 bg-pink-50/70 ring-4 ring-pink-100', 'text' => 'text-pink-700', 'sim' => 'offer'],
        'local' => ['icon' => 'from-red-500 to-rose-700', 'on' => 'border-red-400 bg-red-50/70 ring-4 ring-red-100', 'text' => 'text-red-700', 'sim' => 'physical'],
        'local_fast' => ['icon' => 'from-amber-400 to-orange-600', 'on' => 'border-orange-400 bg-orange-50/70 ring-4 ring-orange-100', 'text' => 'text-orange-700', 'sim' => 'fast'],
        'local_esim' => ['icon' => 'from-violet-500 to-purple-700', 'on' => 'border-violet-400 bg-violet-50/70 ring-4 ring-violet-100', 'text' => 'text-violet-700', 'sim' => 'esim'],
        'europe' => ['icon' => 'from-blue-600 to-indigo-800', 'on' => 'border-blue-400 bg-blue-50/70 ring-4 ring-blue-100', 'text' => 'text-blue-700', 'sim' => 'physical'],
        'europe_esim' => ['icon' => 'from-indigo-500 to-violet-800', 'on' => 'border-indigo-400 bg-indigo-50/70 ring-4 ring-indigo-100', 'text' => 'text-indigo-700', 'sim' => 'esim'],
    ];
@endphp

@php
    // Columns follow the number of categories, so no card is left alone on a row
    $categoryGrid = match (count($tabs)) {
        1 => 'grid-cols-1 sm:grid-cols-2',
        2 => 'grid-cols-2',
        3 => 'grid-cols-2 sm:grid-cols-3',
        4 => 'grid-cols-2 lg:grid-cols-4',
        5 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
        default => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6',
    };
@endphp
<div class="grid {{ $categoryGrid }} gap-3" role="tablist">
    @foreach($tabs as $tab)
        @php
            $catTheme = $categoryThemes[$tab] ?? $categoryThemes['local'];
            $catPackages = $packagesByTab->get($tab, collect());
            $isOpen = $current === $tab;
            $catHint = match (true) {
                $tab === 'offers' => __('messages.sim_tab_hint_offers'),
                $tab === 'local_fast' => __('messages.sim_tab_hint_fast'),
                str_ends_with($tab, '_esim') => __('messages.sim_tab_hint_esim'),
                default => __('messages.sim_tab_hint_physical'),
            };
            $catMinPrice = $catPackages->min('price');
            $catHidden = $catPackages->where('is_active', false)->count();
            $catUnlinked = $catPackages->filter(fn ($p) => $p->is_active && !$p->isApiReady())->count();
        @endphp
        <button type="button" role="tab" aria-selected="{{ $isOpen ? 'true' : 'false' }}"
            wire:click="{{ $action }}('{{ $tab }}')" wire:key="sim-category-{{ $mode }}-{{ $tab }}"
            class="group relative text-start rounded-2xl border-2 p-3 sm:p-4 transition-all duration-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-slate-200 {{ $isOpen ? $catTheme['on'] . ' shadow-soft' : 'border-slate-100 bg-white hover:border-slate-200 hover:-translate-y-0.5 hover:shadow-soft' }}">

            @if($isOpen)
                <span class="absolute -top-2 -end-2 w-6 h-6 rounded-full bg-gradient-to-br {{ $catTheme['icon'] }} text-white flex items-center justify-center shadow ring-2 ring-white">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"></path></svg>
                </span>
            @endif

            <div class="flex items-center gap-3">
                {{-- Colored tile with a small SIM (chip, QR for eSIM, bolt for fast) and the flag --}}
                <div class="relative shrink-0 w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br {{ $catTheme['icon'] }} flex items-center justify-center shadow-md">
                    @if($catTheme['sim'] === 'offer')
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                    @elseif($catTheme['sim'] === 'fast')
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2L4.09 12.6a.5.5 0 00.38.82H11l-1 8.58L18.91 11.4a.5.5 0 00-.38-.82H13l1-8.58z"/></svg>
                    @else
                        <svg class="w-5 h-6" viewBox="0 0 60 78" aria-hidden="true">
                            <path d="M8 1H40L59 20V69A8 8 0 0 1 51 77H8A8 8 0 0 1 0 69V9A8 8 0 0 1 8 1Z" fill="#ffffff" />
                            @if($catTheme['sim'] === 'esim')
                                <g fill="#334155"><rect x="12" y="28" width="12" height="12" rx="2" /><rect x="35" y="28" width="12" height="12" rx="2" /><rect x="12" y="50" width="12" height="12" rx="2" /><rect x="35" y="50" width="6" height="6" rx="1" /></g>
                            @else
                                <rect x="12" y="28" width="35" height="30" rx="5" fill="#fcd34d" />
                            @endif
                        </svg>
                    @endif
                    @if($tab !== 'offers')
                        <span class="absolute -bottom-1.5 -end-1.5 text-sm leading-none drop-shadow">{{ \App\Models\SimPackage::tabFlag($tab) }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-black leading-tight {{ $isOpen ? $catTheme['text'] : 'text-slate-800' }}">{{ __('messages.sim_tab_' . $tab) }}</div>
                    <div class="text-[11px] font-semibold text-slate-500 mt-0.5 truncate">{{ $catHint }}</div>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-bold">
                <span class="text-slate-500">{{ __('messages.sim_network_packages_count', ['count' => $catPackages->count()]) }}</span>
                @if($mode === 'buy' && $catMinPrice !== null)
                    <span class="{{ $isOpen ? $catTheme['text'] : 'text-slate-700' }}">· {{ __('messages.sim_category_from', ['price' => \App\Models\SimPackage::formatPrice($catMinPrice)]) }}</span>
                @endif
                @if($mode === 'admin' && $catHidden > 0)
                    <span class="rounded-full bg-slate-100 text-slate-500 px-1.5">{{ __('messages.sim_category_hidden', ['count' => $catHidden]) }}</span>
                @endif
                @if($mode === 'admin' && $catUnlinked > 0)
                    <span class="rounded-full bg-amber-100 text-amber-800 px-1.5">{{ __('messages.sim_category_unlinked', ['count' => $catUnlinked]) }}</span>
                @endif
            </div>
        </button>
    @endforeach
</div>
