{{-- Our balance at each connected supplier (SIM managers only). Expects $supplierBalances ([] = hidden). --}}
@if(!empty($supplierBalances))
    <span class="inline-flex flex-wrap items-center gap-1.5">
        @foreach($supplierBalances as $sb)
            <span wire:key="supplier-balance-{{ $sb['supplier'] }}"
                class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full border {{ $sb['ok'] ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100' }}"
                @if($sb['primary'] && count($supplierBalances) > 1) title="{{ __('messages.sim_supplier_primary') }}" @endif>
                @if($sb['primary'] && count($supplierBalances) > 1)<span aria-hidden="true">⭐</span>@endif
                @if($sb['ok'])
                    {{ __('messages.sim_supplier_balance_of', ['supplier' => $sb['supplier']]) }}: <span class="font-black" dir="ltr">{{ number_format($sb['balance'], 2) }} ₺</span>
                @else
                    <span title="{{ $sb['error'] }}">{{ __('messages.sim_supplier_not_connected_of', ['supplier' => $sb['supplier']]) }} <span class="font-mono text-[10px] opacity-70" dir="ltr">({{ $sb['error'] }})</span></span>
                @endif
            </span>
        @endforeach
        <button type="button" wire:click="refreshSupplierBalance" wire:loading.class="animate-spin" wire:target="refreshSupplierBalance"
            title="{{ __('messages.sim_refresh') }}" class="text-slate-400 hover:text-slate-700 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </button>
    </span>
@endif
