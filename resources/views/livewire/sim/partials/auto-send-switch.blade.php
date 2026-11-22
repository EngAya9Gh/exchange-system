{{--
    Emergency switch for automatic loading (kontor86). Expects $autoSendConfigured, $autoSendPause (null = running),
    $variant: "card" (supplier settings screen) or "bar" (orders screen, compact).
    The component must use App\Livewire\Sim\Concerns\ControlsAutoSend.
--}}
@php
    $paused = $autoSendPause !== null;
    $pausedBy = $paused && ($autoSendPause['by'] || $autoSendPause['at'])
        ? __('messages.sim_auto_send_paused_by', [
            'name' => $autoSendPause['by'] ?: '-',
            'at' => $autoSendPause['at']?->format('Y-m-d H:i') ?? '-',
        ])
        : null;
@endphp

@if (session()->has('auto_send_flash'))
    <div class="mb-4 p-3 rounded-xl text-sm font-bold border {{ $paused ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-100' }}">
        {{ session('auto_send_flash') }}
    </div>
@endif

@if(($variant ?? 'card') === 'card')
    <div class="rounded-[24px] border-2 p-5 sm:p-6 {{ $paused ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-100 shadow-soft' }}">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-black text-slate-800">{{ __('messages.sim_auto_send_title') }}</h3>
                    @if(!$autoSendConfigured)
                        <span class="text-xs font-black px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">{{ __('messages.sim_auto_send_not_set') }}</span>
                    @elseif($paused)
                        <span class="text-xs font-black px-2.5 py-1 rounded-full bg-amber-200 text-amber-900">⏸️ {{ __('messages.sim_auto_send_off') }}</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-black px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>{{ __('messages.sim_auto_send_on') }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed max-w-xl">
                    {{ $paused ? __('messages.sim_auto_send_off_desc') : __('messages.sim_auto_send_on_desc') }}
                </p>
                @if($pausedBy)
                    <p class="text-[11px] font-bold text-amber-800 mt-1.5">{{ $pausedBy }}</p>
                @endif
            </div>

            @if($autoSendConfigured)
                @if($paused)
                    <button type="button" wire:click="resumeAutoSend" wire:confirm="{{ __('messages.sim_auto_send_resume_confirm') }}" wire:loading.attr="disabled"
                        class="shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-sm transition disabled:opacity-60">
                        ▶️ {{ __('messages.sim_auto_send_resume') }}
                    </button>
                @else
                    <button type="button" wire:click="pauseAutoSend" wire:confirm="{{ __('messages.sim_auto_send_pause_confirm') }}" wire:loading.attr="disabled"
                        class="shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 hover:bg-rose-700 px-5 py-3 text-sm font-black text-white shadow-sm transition disabled:opacity-60">
                        ⏸️ {{ __('messages.sim_auto_send_pause') }}
                    </button>
                @endif
            @endif
        </div>
    </div>
@else
    {{-- Compact bar on the orders screen --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-4 py-3 {{ $paused ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-100' }}">
        <div class="text-sm">
            @if($paused)
                <span class="font-black text-amber-900">⏸️ {{ __('messages.sim_auto_send_banner') }}</span>
                @if($pausedBy)<span class="block text-[11px] font-bold text-amber-800 mt-0.5">{{ $pausedBy }}</span>@endif
            @else
                <span class="inline-flex items-center gap-1.5 font-bold text-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ __('messages.sim_auto_send_title') }}: {{ __('messages.sim_auto_send_on') }}
                </span>
            @endif
        </div>
        @if($paused)
            <button type="button" wire:click="resumeAutoSend" wire:confirm="{{ __('messages.sim_auto_send_resume_confirm') }}" wire:loading.attr="disabled"
                class="shrink-0 px-4 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white transition disabled:opacity-60">
                ▶️ {{ __('messages.sim_auto_send_resume') }}
            </button>
        @else
            <button type="button" wire:click="pauseAutoSend" wire:confirm="{{ __('messages.sim_auto_send_pause_confirm') }}" wire:loading.attr="disabled"
                class="shrink-0 px-4 py-2 rounded-xl text-xs font-black bg-white border border-rose-200 text-rose-700 hover:bg-rose-50 transition disabled:opacity-60">
                ⏸️ {{ __('messages.sim_auto_send_pause') }}
            </button>
        @endif
    </div>
@endif
