@php $inputClass = 'w-full bg-slate-50 border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition'; @endphp

<div class="max-w-3xl space-y-6">
    {{-- Credentials --}}
    <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8">
        <h3 class="text-xl font-black text-slate-800">{{ __('messages.kontor_title') }}</h3>
        <p class="text-xs text-slate-500 mt-1 mb-6 leading-relaxed">{{ __('messages.kontor_desc') }}</p>

        @if (session()->has('kontor_saved'))
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 font-bold">{{ session('kontor_saved') }}</div>
        @endif

        <form wire:submit="save" class="space-y-5">
            <div>
                <label class="block text-sm font-bold text-slate-500 mb-2">{{ __('messages.kontor_dealer_code') }}</label>
                <input type="text" wire:model="dealerCode" dir="ltr" autocomplete="off" class="{{ $inputClass }}" placeholder="5392065498">
                @error('dealerCode') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div x-data="{ show: false }">
                <label class="block text-sm font-bold text-slate-500 mb-2">{{ __('messages.kontor_password') }}</label>
                <div class="relative" dir="ltr">
                    <input :type="show ? 'text' : 'password'" wire:model="password" autocomplete="new-password" class="{{ $inputClass }} pr-12">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                @error('password') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="save,testConnection"
                    class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 py-3.5 px-4 text-sm font-black text-white shadow-soft transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">{{ __('messages.kontor_save_and_test') }}</span>
                    <span wire:loading wire:target="save">{{ __('messages.kontor_testing') }}</span>
                </button>
                <button type="button" wire:click="testConnection" wire:loading.attr="disabled" wire:target="save,testConnection"
                    class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 py-3.5 px-4 text-sm font-black text-slate-700 transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="testConnection">{{ __('messages.kontor_test_connection') }}</span>
                    <span wire:loading wire:target="testConnection">{{ __('messages.kontor_testing') }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Test result --}}
    @if($test)
        <div class="rounded-[24px] border-2 p-5 sm:p-6 {{ $test['ok'] ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' }}">
            @if($test['ok'])
                <div class="text-lg font-black text-emerald-800">✅ {{ __('messages.kontor_connected') }}</div>
                <div class="text-sm font-bold text-emerald-700 mt-2">
                    {{ __('messages.sim_supplier_balance') }}: <span class="font-black text-xl" dir="ltr">{{ number_format($test['balance'], 2) }} ₺</span>
                </div>
            @else
                <div class="text-lg font-black text-rose-800">❌ {{ __('messages.kontor_not_connected') }}</div>
                <div class="text-sm font-bold text-rose-700 mt-2 leading-relaxed">{{ $this->explanation($test['error']) }}</div>
                <div class="text-[11px] text-rose-500 mt-1 font-mono" dir="ltr">{{ $test['error'] }}</div>
            @endif

            <div class="mt-4 pt-4 border-t {{ $test['ok'] ? 'border-emerald-200' : 'border-rose-200' }}">
                <div class="text-xs font-bold text-slate-600">{{ __('messages.kontor_server_ip') }}</div>
                @if($test['ip'])
                    <div class="flex items-center gap-2 mt-1" x-data="{ copied: false }">
                        <span class="text-xl font-black text-slate-800 font-mono" dir="ltr">{{ $test['ip'] }}</span>
                        <button type="button" @click="navigator.clipboard.writeText(@js($test['ip'])); copied = true; setTimeout(() => copied = false, 1500)"
                            class="text-xs font-bold px-2.5 py-1 rounded-lg bg-white/70 text-slate-600 hover:bg-white transition">
                            <span x-show="!copied">{{ __('messages.sim_copy') }}</span><span x-show="copied" x-cloak>✓</span>
                        </button>
                    </div>
                    @unless($test['ok'])
                        <p class="text-xs text-slate-600 mt-1">{{ __('messages.kontor_server_ip_hint') }}</p>
                    @endunless
                @else
                    <div class="text-sm text-slate-500 mt-1">{{ __('messages.kontor_server_ip_unknown') }}</div>
                @endif
            </div>

            <div class="text-[11px] text-slate-400 mt-3" dir="ltr">{{ $test['at'] }}</div>
        </div>
    @endif
</div>
