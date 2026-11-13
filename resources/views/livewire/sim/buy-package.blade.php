@use('App\Models\SimPackage')
@php
    $currentPackages = $packagesByTab->get($region, collect());
@endphp

<div class="space-y-6">
    {{-- Check balance (remaining data/days) on the supplier's page --}}
    <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 shrink-0 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-800">{{ __('messages.sim_check_balance') }}</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ __('messages.sim_check_balance_desc') }}</p>
            </div>
        </div>
        <div class="shrink-0 flex flex-col sm:flex-row gap-2">
            <a href="{{ route('sims.balance') }}" target="_blank" rel="noopener"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-black text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-100 transition">
                {{ __('messages.sim_check_balance_open') }}
                <svg class="w-4 h-4 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
            {{-- Shops send this link to their customers so they check their balance on our site --}}
            <button type="button" x-data="{ copied: false }" title="{{ __('messages.sim_share_link_hint') }}"
                @click="navigator.clipboard.writeText(@js(route('sims.balance'))); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-black text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                <span x-show="!copied">{{ __('messages.sim_share_link') }}</span>
                <span x-show="copied" x-cloak>✓ {{ __('messages.sim_link_copied') }}</span>
            </button>
        </div>
    </div>

    {{-- Package picker --}}
    <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                <svg class="w-6 h-6 text-primary-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3h6l4 4v12a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11h6v6H9z"></path>
                </svg>
                {{ __('messages.sim_choose_package') }}
                @include('livewire.sim.partials.supplier-balance')
            </h3>

            {{-- One tab per region, eSIM packages in their own tab (never mixed with physical SIMs) --}}
            <div class="flex bg-slate-100 rounded-2xl p-1 w-full lg:w-auto gap-1">
                @foreach($tabs as $tab)
                    @php $isEsimTab = str_ends_with($tab, '_esim'); @endphp
                    <button type="button" wire:click="selectRegion('{{ $tab }}')" wire:key="sim-tab-{{ $tab }}"
                        class="flex-1 lg:flex-none min-w-0 px-2.5 sm:px-4 py-2 rounded-xl text-sm font-bold transition flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 {{ $region === $tab ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        <span class="flex items-center gap-1.5 whitespace-nowrap">
                            <span class="text-base leading-none">{{ SimPackage::tabFlag($tab) }}</span>
                            {{ __('messages.sim_tab_' . $tab) }}
                        </span>
                        <span class="text-[10px] sm:text-[11px] font-semibold whitespace-nowrap {{ $region === $tab ? 'opacity-80' : 'opacity-60' }} {{ $isEsimTab ? 'text-indigo-600' : '' }}">
                            {{ $isEsimTab ? __('messages.sim_tab_hint_esim') : __('messages.sim_tab_hint_physical') }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        @if (session()->has('sim_order_success'))
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="font-bold">{{ session('sim_order_success') }}</span>
            </div>
        @endif

        @error('selectedPackageId')
            <div class="mb-4 text-sm font-bold text-rose-600">{{ $message }}</div>
        @enderror

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse($currentPackages as $package)
                @include('livewire.sim.partials.package-card', [
                    'package' => $package,
                    'isSelected' => $selectedPackageId === $package->id,
                    'instant' => $apiConfigured && $package->isApiReady(),
                ])
            @empty
                <div class="col-span-full text-center text-slate-400 py-10 font-bold">{{ __('messages.sim_no_packages') }}</div>
            @endforelse
        </div>
    </div>

    {{-- Order form --}}
    @if($selectedPackage)
        <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8 animate-fade-in-up" wire:key="sim-form-{{ $selectedPackage->id }}">
            <h3 class="text-lg font-black text-slate-800 mb-6">{{ __('messages.sim_order_details') }}</h3>

            @error('order')
                <div class="mb-6 p-4 bg-rose-50 text-rose-700 rounded-xl border border-rose-100 font-bold">{{ $message }}</div>
            @enderror

            <form class="space-y-6"
                x-data
                @submit.prevent="Swal.fire({
                    title: @js(__('messages.sim_confirm_title')),
                    text: @js(__('messages.sim_confirm_text', ['package' => $selectedPackage->localized_name, 'price' => SimPackage::formatPrice($selectedPackage->price), 'phone' => '__PHONE__'])).replace('__PHONE__', (v => {
                        @if($selectedPackage->isEsim())
                            return v || '-';
                        @else
                            const d = v.replace(/\D/g, ''), m = d.match(/^(?:0090|90|0)?50(\d{8})$/);
                            return @js(\App\Livewire\Sim\BuyPackage::PHONE_PREFIX) + (m ? m[1] : d);
                        @endif
                    })($wire.phoneNumber || '')),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: @js(__('messages.sim_yes_order')),
                    cancelButtonText: @js(__('messages.sim_cancel'))
                }).then((result) => { if (result.isConfirmed) $wire.placeOrder() })">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="sim-phone" class="block text-sm font-bold text-gray-700 mb-2">
                            {{ $selectedPackage->isEsim() ? __('messages.sim_phone_label_esim') : __('messages.sim_phone_label_physical') }}
                            <span class="text-rose-500">*</span>
                        </label>
                        @if($selectedPackage->isEsim())
                            <input id="sim-phone" type="tel" inputmode="tel" dir="ltr" autocomplete="off" wire:model="phoneNumber"
                                placeholder="5XX XXX XX XX"
                                x-data x-init="$nextTick(() => $el.focus())"
                                class="block w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm font-bold text-lg tracking-wide placeholder:text-slate-300 placeholder:font-normal">
                        @else
                            {{-- "50" is fixed; the user types only the last 8 digits --}}
                            <div class="flex rounded-xl shadow-sm" dir="ltr">
                                <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-gray-300 bg-slate-100 text-slate-700 font-black text-lg tracking-wide select-none">{{ \App\Livewire\Sim\BuyPackage::PHONE_PREFIX }}</span>
                                <input id="sim-phone" type="tel" inputmode="numeric" autocomplete="off" maxlength="20" wire:model="phoneNumber"
                                    placeholder="{{ __('messages.sim_phone_last8_placeholder') }}"
                                    x-data x-init="$nextTick(() => $el.focus())"
                                    class="block w-full min-w-0 rounded-none rounded-r-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 font-bold text-lg tracking-widest placeholder:text-slate-300 placeholder:font-normal placeholder:text-sm placeholder:tracking-normal">
                            </div>
                        @endif
                        <p class="text-xs text-slate-500 mt-2 flex items-start gap-1.5">
                            <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ $selectedPackage->isEsim() ? __('messages.sim_phone_hint_esim') : __('messages.sim_phone_hint_physical') }}
                        </p>
                        @error('phoneNumber') <span class="text-rose-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="sim-customer" class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.sim_customer_name') }}</label>
                        <input id="sim-customer" type="text" wire:model="customerName" maxlength="100"
                            class="block w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm">
                        @error('customerName') <span class="text-rose-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="sim-notes" class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.sim_notes') }}</label>
                        <input id="sim-notes" type="text" wire:model="notes" maxlength="500"
                            class="block w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm">
                        @error('notes') <span class="text-rose-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Summary --}}
                <div class="bg-slate-50 rounded-2xl border border-slate-100 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-black text-slate-800">{{ $selectedPackage->localized_name }}</div>
                        @if($selectedPackage->network_name)
                            <div class="text-xs font-bold text-slate-600 mt-1">{{ __('messages.sim_network') }}: <span dir="ltr">{{ $selectedPackage->network_name }}</span></div>
                        @endif
                        <div class="text-xs font-bold text-slate-500 mt-1">
                            @if($isExemptFromCharge)
                                {{ __('messages.sim_admin_no_charge') }}
                            @else
                                {{ __('messages.sim_will_be_deducted', ['amount' => SimPackage::formatPrice($selectedPackage->price)]) }}
                            @endif
                        </div>
                    </div>
                    <div class="text-2xl font-black text-primary-600 shrink-0" dir="ltr">{{ SimPackage::formatPrice($selectedPackage->price) }} ₺</div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
                    <button type="button" wire:click="clearSelection"
                        class="px-6 py-3 rounded-xl text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                        {{ __('messages.sim_cancel') }}
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                        class="px-8 py-3 rounded-xl text-sm font-black text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition disabled:opacity-60 disabled:cursor-wait flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="placeOrder">{{ __('messages.sim_submit_order') }}</span>
                        <span wire:loading wire:target="placeOrder">{{ __('messages.sim_submitting') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
