{{--
    Emergency switches for automatic loading, one per connected supplier (orders screen, SIM managers).
    Expects $suppliersState (KontorApiService::overview()). The component uses App\Livewire\Sim\Concerns\ControlsAutoSend.
--}}
@php
    $configured = collect($suppliersState)->where('configured', true);
    $allPaused = $configured->isNotEmpty() && $configured->where('active', true)->isEmpty();
    $several = $configured->count() > 1;
@endphp

@if (session()->has('auto_send_flash'))
    <div class="mb-4 p-3 rounded-xl text-sm font-bold border {{ $allPaused ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-100' }}">
        {{ session('auto_send_flash') }}
    </div>
@endif

<div class="mb-5 rounded-2xl border px-4 py-3 {{ $allPaused ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-100' }}">
    @if($allPaused)
        <div class="text-sm font-black text-amber-900 mb-2">⏸️ {{ __('messages.sim_auto_send_banner') }}</div>
    @endif
    <div class="flex flex-wrap items-center gap-2">
        @foreach($configured as $state)
            @php $paused = $state['pause'] !== null; @endphp
            <div wire:key="auto-send-{{ $state['supplier'] }}" class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs">
                @if($state['primary'] && $several)<span title="{{ __('messages.sim_supplier_primary') }}">⭐</span>@endif
                <span class="font-black text-slate-800" dir="ltr">{{ $state['supplier'] }}</span>
                @if($paused)
                    <span class="font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-900">⏸️ {{ __('messages.sim_auto_send_off') }}</span>
                    @if($state['pause']['by'] || $state['pause']['at'])
                        <span class="text-[11px] font-bold text-amber-800">{{ __('messages.sim_auto_send_paused_by', ['name' => $state['pause']['by'] ?: '-', 'at' => $state['pause']['at']?->format('Y-m-d H:i') ?? '-']) }}</span>
                    @endif
                    @if(count($state['exclusive']))
                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-900" title="{{ implode(' · ', $state['exclusive']) }}">⚠️ {{ __('messages.sim_supplier_manual_now', ['count' => count($state['exclusive'])]) }}</span>
                    @endif
                    <button type="button" wire:click="resumeAutoSend('{{ $state['supplier'] }}')" wire:confirm="{{ __('messages.sim_auto_send_resume_confirm') }}" wire:loading.attr="disabled"
                        class="px-3 py-1 rounded-lg font-black bg-emerald-600 hover:bg-emerald-700 text-white transition disabled:opacity-60">
                        ▶️ {{ __('messages.sim_auto_send_resume') }}
                    </button>
                @else
                    <span class="inline-flex items-center gap-1 font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ __('messages.sim_auto_send_on') }}
                    </span>
                    <button type="button" wire:click="pauseAutoSend('{{ $state['supplier'] }}')"
                        wire:confirm="{{ count($state['exclusive']) ? __('messages.sim_supplier_pause_confirm_exclusive', ['supplier' => $state['supplier'], 'count' => count($state['exclusive'])]) : __('messages.sim_auto_send_pause_confirm') }}" wire:loading.attr="disabled"
                        class="px-3 py-1 rounded-lg font-black bg-white border border-rose-200 text-rose-700 hover:bg-rose-50 transition disabled:opacity-60">
                        ⏸️ {{ __('messages.sim_auto_send_pause') }}
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
