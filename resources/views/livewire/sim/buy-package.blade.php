@use('App\Models\SimOrder')
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
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                <svg class="w-6 h-6 text-primary-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3h6l4 4v12a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11h6v6H9z"></path>
                </svg>
                {{ __('messages.sim_choose_package') }}
                @include('livewire.sim.partials.supplier-balance')
            </h3>
        </div>

        {{-- One card per category (Turkey, fast, eSIM, Europe…), never mixing physical SIMs and eSIMs --}}
        <div class="mb-6">
            @include('livewire.sim.partials.category-cards', ['current' => $region, 'action' => 'selectRegion', 'mode' => 'buy'])
        </div>

        @error('selectedPackageId')
            <div class="mb-4 text-sm font-bold text-rose-600">{{ $message }}</div>
        @enderror

        {{-- One block per network when a tab holds several (e.g. Tele CARD and Türk Telekom eSIMs) --}}
        @php $networkGroups = $currentPackages->groupBy(fn ($p) => ($p->is_fast ? 'fast|' : '') . ($p->network_name ?: '—')); @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse($networkGroups as $networkName => $networkPackages)
                @if($networkGroups->count() > 1)
                    @php $isFastGroup = str_starts_with($networkName, 'fast|'); @endphp
                    <div class="col-span-full flex items-center gap-3 {{ $loop->first ? '' : 'mt-4' }}" wire:key="sim-network-{{ $loop->index }}">
                        @if($isFastGroup)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-amber-400 to-orange-600 text-white text-xs font-black px-3 py-1 shadow-md shadow-orange-500/20">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2L4.09 12.6a.5.5 0 00.38.82H11l-1 8.58L18.91 11.4a.5.5 0 00-.38-.82H13l1-8.58z"/></svg>
                                {{ __('messages.sim_fast_ribbon') }}
                                <span class="opacity-60" aria-hidden="true">·</span>
                                <span class="opacity-90" dir="ltr">{{ substr($networkName, 5) }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-800 text-white text-xs font-black px-3 py-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                                <span dir="ltr">{{ $networkName }}</span>
                            </span>
                        @endif
                        <span class="text-xs font-bold text-slate-400">{{ __('messages.sim_network_packages_count', ['count' => $networkPackages->count()]) }}</span>
                        <span class="h-px flex-1 bg-slate-100"></span>
                    </div>
                @endif
                @foreach($networkPackages as $package)
                    @include('livewire.sim.partials.package-card', [
                        'package' => $package,
                        'isSelected' => $selectedPackageId === $package->id,
                        'instant' => collect($autoSuppliers)->contains(fn (string $supplier) => $package->isApiReadyFor($supplier)),
                    ])
                @endforeach
            @empty
                <div class="col-span-full text-center text-slate-400 py-10 font-bold">{{ __('messages.sim_no_packages') }}</div>
            @endforelse
        </div>
    </div>

    {{-- Order form --}}
    @if($selectedPackage)
        <div class="relative bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8 animate-fade-in-up" wire:key="sim-form-{{ $selectedPackage->id }}">
            {{-- While the order is being sent (the supplier call can take a few seconds) --}}
            <div wire:loading.flex wire:target="placeOrder" class="absolute inset-0 z-10 rounded-[24px] bg-white/85 backdrop-blur-[2px] flex-col items-center justify-center gap-3 text-center px-6">
                <svg class="w-10 h-10 text-primary-600 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <p class="text-base font-black text-slate-800">{{ __('messages.sim_submitting') }}</p>
                <p class="text-xs font-semibold text-slate-500">{{ __('messages.sim_sending_hint') }}</p>
            </div>

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

                    {{-- SIM managers: send this order to one supplier, whatever the settings say --}}
                    @if($supplierOptions)
                        <div class="md:col-span-2 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                            <label for="sim-supplier" class="block text-sm font-bold text-gray-700 mb-2">🔌 {{ __('messages.sim_supplier_choice_label') }}</label>
                            <select id="sim-supplier" wire:model.live="supplierChoice"
                                class="block w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm font-bold">
                                <option value="">
                                    {{ $supplierOptions['auto'] ? __('messages.sim_supplier_choice_auto', ['supplier' => $supplierOptions['auto']]) : __('messages.sim_supplier_choice_auto_manual') }}
                                </option>
                                @foreach($supplierOptions['suppliers'] as $supplierName => $option)
                                    @php
                                        $selectable = $option['linked'] && $option['configured'];
                                        $optionLabel = implode(' · ', array_filter([
                                            $supplierName,
                                            !$option['linked'] ? __('messages.sim_supplier_choice_not_linked') : null,
                                            $option['linked'] && !$option['configured'] ? __('messages.sim_supplier_choice_not_connected') : null,
                                            $selectable && $option['cost'] !== null ? __('messages.sim_supplier_choice_cost', ['cost' => SimPackage::formatPrice($option['cost'])]) : null,
                                            $selectable && $option['paused'] ? __('messages.sim_supplier_choice_paused') : null,
                                        ]));
                                    @endphp
                                    <option value="{{ $supplierName }}" @disabled(!$selectable)>{{ $optionLabel }}</option>
                                @endforeach
                                <option value="{{ \App\Services\SimOrderService::MANUAL_LOAD }}">✋ {{ __('messages.sim_supplier_choice_manual') }}</option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">{{ $supplierChoice === \App\Services\SimOrderService::MANUAL_LOAD ? __('messages.sim_supplier_choice_manual_hint') : __('messages.sim_supplier_choice_hint') }}</p>
                            @error('supplierChoice') <span class="text-rose-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif
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

    {{-- Confirmation window after an order: what was sent, what was deducted, and the live status --}}
    @if($lastOrder)
        @php
            $receiptUi = [
                'loading' => ['halo' => 'bg-emerald-100', 'circle' => 'bg-emerald-500', 'icon' => 'check', 'pill' => 'bg-sky-100 text-sky-700', 'title' => 'sim_receipt_title_sent', 'status' => 'sim_status_loading'],
                'checking' => ['halo' => 'bg-emerald-100', 'circle' => 'bg-emerald-500', 'icon' => 'check', 'pill' => 'bg-amber-100 text-amber-800', 'title' => 'sim_receipt_title_sent', 'status' => 'sim_receipt_state_checking'],
                'manual' => ['halo' => 'bg-emerald-100', 'circle' => 'bg-emerald-500', 'icon' => 'check', 'pill' => 'bg-emerald-100 text-emerald-700', 'title' => 'sim_receipt_title_sent', 'status' => 'sim_status_completed', 'desc' => 'sim_receipt_desc_queued'],
                'queued' => ['halo' => 'bg-amber-100', 'circle' => 'bg-amber-500', 'icon' => 'clock', 'pill' => 'bg-amber-100 text-amber-800', 'title' => 'sim_receipt_title_received', 'status' => 'sim_status_pending'],
                'loaded' => ['halo' => 'bg-emerald-100', 'circle' => 'bg-emerald-500', 'icon' => 'double', 'pill' => 'bg-emerald-100 text-emerald-700', 'title' => 'sim_receipt_title_loaded', 'status' => 'sim_status_loaded'],
                'failed' => ['halo' => 'bg-rose-100', 'circle' => 'bg-rose-500', 'icon' => 'x', 'pill' => 'bg-rose-100 text-rose-700', 'title' => 'sim_receipt_title_failed', 'status' => 'sim_status_rejected'],
            ][$receiptState];
            $receiptOpen = in_array($receiptState, ['loading', 'checking', 'queued'], true);
            $receiptCharged = (float) $lastOrder->charged_amount;
            $receiptReason = $receiptState === 'failed' && filled($lastOrder->api_message) ? SimOrder::publicSupplierReason($lastOrder->api_message) : null;
            $receiptCopy = implode("\n", [
                '📶 ' . $lastOrder->localized_package_name,
                '📱 ' . $lastOrder->phone_number,
                '🧾 ' . $lastOrder->order_number,
                '• ' . __('messages.' . $receiptUi['status']),
            ]);
        @endphp
        <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="sim-receipt-title"
            wire:key="sim-receipt-{{ $lastOrder->id }}"
            x-data="{ copied: null, copy(text, what) { navigator.clipboard?.writeText(text); this.copied = what; setTimeout(() => this.copied = null, 2000) } }"
            x-init="$nextTick(() => $refs.receiptPrimary?.focus())"
            @keydown.escape.window="$wire.closeReceipt()">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeReceipt"></div>

            <div class="relative w-full sm:max-w-md max-h-[92vh] overflow-y-auto bg-white rounded-t-[28px] sm:rounded-[28px] shadow-2xl animate-fade-in-up">
                {{-- Re-reads the order while it is still open, so the window turns into ✅ / ❌ by itself --}}
                @if($receiptOpen)
                    <div wire:poll.10s wire:key="sim-receipt-poll"></div>
                @endif

                <button type="button" wire:click="closeReceipt" aria-label="{{ __('messages.sim_receipt_close') }}"
                    class="absolute top-4 end-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <div class="px-6 pt-9 pb-5 text-center">
                    <div class="relative mx-auto w-20 h-20">
                        <span class="absolute -inset-2 rounded-full {{ $receiptUi['halo'] }} {{ $receiptOpen ? 'animate-pulse' : '' }}"></span>
                        <div class="relative w-20 h-20 rounded-full {{ $receiptUi['circle'] }} text-white flex items-center justify-center shadow-lg">
                            @switch($receiptUi['icon'])
                                @case('clock')
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    @break
                                @case('double')
                                    <svg class="w-11 h-11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2 13l4 4L16 7M10 15l2 2L22 7"></path></svg>
                                    @break
                                @case('x')
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    @break
                                @default
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            @endswitch
                        </div>
                    </div>

                    <h3 id="sim-receipt-title" class="mt-6 text-xl font-black text-slate-800">{{ __('messages.' . $receiptUi['title']) }}</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                        @if($receiptState === 'failed')
                            {{ $receiptCharged > 0 ? __('messages.sim_receipt_desc_failed', ['amount' => SimPackage::formatPrice($receiptCharged)]) : __('messages.sim_receipt_desc_failed_no_charge') }}
                        @else
                            {{ __('messages.' . ($receiptUi['desc'] ?? 'sim_receipt_desc_' . $receiptState)) }}
                        @endif
                    </p>
                    @if($receiptReason)
                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $receiptReason }}</p>
                    @endif

                    <span class="mt-4 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-black {{ $receiptUi['pill'] }}">
                        @if($receiptOpen)
                            <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                        @endif
                        {{ __('messages.' . $receiptUi['status']) }}
                    </span>
                </div>

                <dl class="mx-6 rounded-2xl bg-slate-50 border border-slate-100 divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <dt class="text-slate-500 font-semibold">{{ __('messages.sim_receipt_order_number') }}</dt>
                        <dd class="flex items-center gap-2">
                            <span class="font-black text-slate-800 font-mono" dir="ltr">{{ $lastOrder->order_number }}</span>
                            <button type="button" @click="copy(@js($lastOrder->order_number), 'number')" title="{{ __('messages.sim_receipt_copy') }}"
                                class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-primary-600 hover:border-primary-200 flex items-center justify-center transition">
                                <svg x-show="copied !== 'number'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <svg x-show="copied === 'number'" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </button>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-3">
                        <dt class="text-slate-500 font-semibold shrink-0">{{ __('messages.sim_receipt_package') }}</dt>
                        <dd class="text-end">
                            <div class="font-black text-slate-800">{{ $lastOrder->localized_package_name }}</div>
                            @if($lastOrder->network_name)
                                <div class="text-[11px] font-bold text-slate-400 mt-0.5" dir="ltr">{{ $lastOrder->network_name }}</div>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <dt class="text-slate-500 font-semibold">{{ __('messages.sim_receipt_phone') }}</dt>
                        <dd class="font-black text-slate-800 tracking-wide" dir="ltr">{{ $lastOrder->phone_number }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <dt class="text-slate-500 font-semibold">{{ $receiptState === 'failed' && $receiptCharged > 0 ? __('messages.sim_receipt_refunded') : __('messages.sim_receipt_charged') }}</dt>
                        <dd class="font-black {{ $receiptState === 'failed' ? 'text-emerald-600' : 'text-slate-800' }}" dir="ltr">
                            {{ $receiptCharged > 0 ? SimPackage::formatPrice($receiptCharged) . ' ₺' : __('messages.sim_receipt_no_charge') }}
                        </dd>
                    </div>
                    @if($receiptCharged > 0)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <dt class="text-slate-500 font-semibold">{{ __('messages.sim_receipt_balance') }}</dt>
                            <dd class="font-black text-slate-800" dir="ltr">{{ SimPackage::formatPrice(auth()->user()->balance) }} ₺</dd>
                        </div>
                    @endif
                    @if($showReceiptSupplier && ($lastOrder->api_supplier || $lastOrder->api_supplier_forced))
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <dt class="text-slate-500 font-semibold">{{ __('messages.sim_receipt_supplier') }}</dt>
                            @if($lastOrder->api_supplier)
                                <dd class="font-black text-slate-800" dir="ltr">{{ $lastOrder->api_supplier_forced ? '🎯 ' : '' }}{{ $lastOrder->api_supplier }}</dd>
                            @else
                                <dd class="font-black text-slate-800">✋ {{ __('messages.sim_supplier_choice_manual') }}</dd>
                            @endif
                        </div>
                    @endif
                </dl>

                @if($lastOrder->isEsim() && $receiptState !== 'failed')
                    <p class="mx-6 mt-3 rounded-xl bg-indigo-50 text-indigo-700 text-xs font-bold px-3 py-2.5 flex items-start gap-2">
                        <span aria-hidden="true">📩</span>
                        <span>{{ __('messages.sim_receipt_esim_sms') }}</span>
                    </p>
                @endif
                @if($receiptOpen)
                    <p class="mx-6 mt-3 text-[11px] font-semibold text-slate-400 text-center">{{ __('messages.sim_receipt_live_hint') }}</p>
                @endif

                <div class="p-6 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button" x-ref="receiptPrimary" wire:click="closeReceipt"
                        class="px-5 py-3 rounded-xl text-sm font-black text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition">
                        {{ __('messages.sim_receipt_new_order') }}
                    </button>
                    <button type="button" wire:click="showOrders" @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                        class="px-5 py-3 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        {{ __('messages.sim_receipt_view_orders') }}
                    </button>
                    <button type="button" @click="copy(@js($receiptCopy), 'details')"
                        class="sm:col-span-2 inline-flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-slate-500 hover:text-primary-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span x-show="copied !== 'details'">{{ __('messages.sim_receipt_copy') }}</span>
                        <span x-show="copied === 'details'" x-cloak class="text-emerald-600">✓ {{ __('messages.sim_receipt_copied') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
