{{-- Our balance at kontor86 (SIM managers only). Expects $supplierBalance (null = hidden). --}}
@if($supplierBalance !== null)
    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full border {{ $supplierBalance['ok'] ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100' }}">
        @if($supplierBalance['ok'])
            {{ __('messages.sim_supplier_balance') }}: <span class="font-black" dir="ltr">{{ number_format($supplierBalance['balance'], 2) }} ₺</span>
        @else
            <span title="{{ $supplierBalance['error'] }}">{{ __('messages.sim_supplier_not_connected') }} <span class="font-mono text-[10px] opacity-70" dir="ltr">({{ $supplierBalance['error'] }})</span></span>
        @endif
        <button type="button" wire:click="refreshSupplierBalance" wire:loading.class="animate-spin" wire:target="refreshSupplierBalance"
            title="{{ __('messages.sim_refresh') }}" class="opacity-60 hover:opacity-100 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </button>
    </span>
@endif
