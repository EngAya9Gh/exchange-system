@php $inputClass = 'w-full bg-slate-50 border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition'; @endphp

<div class="max-w-4xl space-y-6">
    {{-- Suppliers: which one is tried first, balance, linked packages, emergency switch --}}
    <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8">
        <h3 class="text-xl font-black text-slate-800">{{ __('messages.sim_suppliers_title') }}</h3>
        <p class="text-xs text-slate-500 mt-1 mb-5 leading-relaxed">{{ __('messages.sim_suppliers_desc') }}</p>

        @if (session()->has('auto_send_flash'))
            <div class="mb-4 p-3 rounded-xl text-sm font-bold border bg-slate-50 text-slate-800 border-slate-200">{{ session('auto_send_flash') }}</div>
        @endif
        @if (session()->has('primary_saved'))
            <div class="mb-4 p-3 rounded-xl text-sm font-bold border bg-emerald-50 text-emerald-800 border-emerald-100">{{ session('primary_saved') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($suppliers as $state)
                @php $paused = $state['pause'] !== null; @endphp
                <div wire:key="supplier-card-{{ $state['supplier'] }}"
                    class="rounded-2xl border-2 p-4 flex flex-col gap-3 {{ $state['primary'] ? 'border-primary-200 bg-primary-50/40' : 'border-slate-100 bg-white' }} {{ $paused ? '!border-amber-200 !bg-amber-50' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-black text-slate-900" dir="ltr">{{ $state['supplier'] }}</span>
                                @if($state['primary'])
                                    <span class="text-[11px] font-black px-2 py-0.5 rounded-full bg-primary-600 text-white">⭐ {{ __('messages.sim_supplier_primary') }}</span>
                                @endif
                            </div>
                            <div class="text-[11px] font-bold text-slate-500 mt-1">{{ __('messages.sim_supplier_packages', ['count' => $state['packages']]) }}</div>
                        </div>
                        @if(!$state['configured'])
                            <span class="shrink-0 text-xs font-black px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">{{ __('messages.sim_auto_send_not_set') }}</span>
                        @elseif($paused)
                            <span class="shrink-0 text-xs font-black px-2.5 py-1 rounded-full bg-amber-200 text-amber-900">⏸️ {{ __('messages.sim_auto_send_off') }}</span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1.5 text-xs font-black px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>{{ __('messages.sim_auto_send_on') }}
                            </span>
                        @endif
                    </div>

                    @if($state['balance'])
                        <div class="text-sm font-bold {{ $state['balance']['ok'] ? 'text-emerald-700' : 'text-rose-700' }}">
                            @if($state['balance']['ok'])
                                {{ __('messages.sim_supplier_balance_of', ['supplier' => $state['supplier']]) }}: <span class="font-black text-base" dir="ltr">{{ number_format($state['balance']['balance'], 2) }} ₺</span>
                            @else
                                {{ __('messages.sim_supplier_not_connected_of', ['supplier' => $state['supplier']]) }} <span class="font-mono text-[10px]" dir="ltr">({{ $state['balance']['error'] }})</span>
                            @endif
                        </div>
                    @endif

                    @if($paused && ($state['pause']['by'] || $state['pause']['at']))
                        <div class="text-[11px] font-bold text-amber-800">{{ __('messages.sim_auto_send_paused_by', ['name' => $state['pause']['by'] ?: '-', 'at' => $state['pause']['at']?->format('Y-m-d H:i') ?? '-']) }}</div>
                    @endif

                    <div class="mt-auto flex flex-wrap gap-2">
                        @if($state['configured'])
                            @if($paused)
                                <button type="button" wire:click="resumeAutoSend('{{ $state['supplier'] }}')" wire:confirm="{{ __('messages.sim_auto_send_resume_confirm') }}" wire:loading.attr="disabled"
                                    class="px-3 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white transition disabled:opacity-60">▶️ {{ __('messages.sim_auto_send_resume') }}</button>
                            @else
                                <button type="button" wire:click="pauseAutoSend('{{ $state['supplier'] }}')" wire:confirm="{{ __('messages.sim_auto_send_pause_confirm') }}" wire:loading.attr="disabled"
                                    class="px-3 py-2 rounded-xl text-xs font-black bg-rose-600 hover:bg-rose-700 text-white transition disabled:opacity-60">⏸️ {{ __('messages.sim_auto_send_pause') }}</button>
                            @endif
                        @endif
                        @unless($state['primary'])
                            <button type="button" wire:click="makePrimary('{{ $state['supplier'] }}')" wire:loading.attr="disabled"
                                class="px-3 py-2 rounded-xl text-xs font-black bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">⭐ {{ __('messages.sim_supplier_make_primary') }}</button>
                        @endunless
                        <button type="button" wire:click="selectSupplier('{{ $state['supplier'] }}')"
                            class="px-3 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 transition">{{ __('messages.sim_supplier_credentials') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Credentials of the selected supplier --}}
    <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-xl font-black text-slate-800">{{ __('messages.kontor_title') }}</h3>
            <div class="inline-flex bg-slate-100 rounded-xl p-1">
                @foreach(array_keys($suppliers) as $name)
                    <button type="button" wire:click="selectSupplier('{{ $name }}')" wire:key="supplier-tab-{{ $name }}"
                        class="px-4 py-1.5 rounded-lg text-sm font-black transition {{ $supplier === $name ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}" dir="ltr">{{ $name }}</button>
                @endforeach
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-2 mb-6 leading-relaxed">{{ __('messages.kontor_desc') }}</p>

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
