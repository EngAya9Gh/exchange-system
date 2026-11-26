@use('App\Models\SimPackage')
@php
    $inputClass = 'block w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm text-sm';
@endphp

<div class="space-y-6">
    <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-xl font-black text-slate-800">{{ __('messages.sim_manage_packages') }}</h3>
                <p class="text-xs text-slate-500 mt-1">{{ __('messages.sim_manage_packages_desc') }}</p>
            </div>
            <button type="button" wire:click="create"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-black text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"></path></svg>
                {{ __('messages.sim_add_package') }}
            </button>
        </div>

        @if (session()->has('sim_package_success'))
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 font-bold">{{ session('sim_package_success') }}</div>
        @endif

        {{-- On sale but linked to no supplier: their orders are loaded by hand from the group --}}
        @if($unlinked->isNotEmpty())
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                @php $anySuggestion = $unlinked->contains(fn ($p) => $p->suggestedLinks() !== []); @endphp
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-black text-amber-900">⚠️ {{ __('messages.sim_unlinked_title', ['count' => $unlinked->count()]) }}</div>
                        <p class="text-xs text-amber-800 mt-1">{{ __('messages.sim_unlinked_desc') }}</p>
                    </div>
                    @if($anySuggestion)
                        <button type="button" wire:click="linkAllSuggested" wire:loading.attr="disabled"
                            class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-black bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.sim_unlinked_link_all') }}</button>
                    @endif
                </div>
                <div class="mt-3 space-y-2">
                    @foreach($unlinked as $unlinkedPackage)
                        @php $suggestion = $unlinkedPackage->suggestedLinks(); @endphp
                        <div wire:key="unlinked-{{ $unlinkedPackage->id }}" class="flex flex-wrap items-center gap-2 rounded-xl bg-white border border-amber-200 px-3 py-2 text-xs">
                            <span class="font-black text-slate-800">{{ $unlinkedPackage->name_ar }}</span>
                            <span class="text-slate-500" dir="ltr">{{ $unlinkedPackage->data_gb }} GB · {{ $unlinkedPackage->durationLabel() }} · {{ SimPackage::formatPrice($unlinkedPackage->price) }} ₺</span>
                            <span class="text-slate-300">→</span>
                            @if($suggestion)
                                @foreach($suggestion as $suggestedSupplier => $suggestedProduct)
                                    <span class="rounded-full bg-emerald-50 text-emerald-800 px-2 py-0.5 font-bold" dir="ltr">{{ $suggestedSupplier }}: {{ $suggestedProduct['name'] }} · {{ $suggestedProduct['code'] }}</span>
                                @endforeach
                                <button type="button" wire:click="linkSuggested({{ $unlinkedPackage->id }})" class="ms-auto px-2.5 py-1 rounded-lg font-black bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.sim_unlinked_link') }}</button>
                            @else
                                <span class="text-amber-800 font-bold">{{ __('messages.sim_unlinked_no_match') }}</span>
                                <button type="button" wire:click="edit({{ $unlinkedPackage->id }})" class="ms-auto px-2.5 py-1 rounded-lg font-black bg-white border border-amber-300 text-amber-900 hover:bg-amber-100 transition">{{ __('messages.sim_unlinked_pick') }}</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Categories as cards: the open one's packages are listed below --}}
        <div class="mb-6">
            @include('livewire.sim.partials.category-cards', ['current' => $currentCategory, 'action' => 'selectCategory', 'mode' => 'admin'])
        </div>

        @foreach([$currentCategory] as $regionKey)
            <div class="mb-8 last:mb-0" wire:key="sim-manage-category-{{ $regionKey }}">
                <h4 class="text-sm font-black text-slate-600 mb-3 flex items-center gap-2">
                    <span>{{ SimPackage::tabFlag($regionKey) }}</span>
                    <span>{{ __('messages.sim_tab_' . $regionKey) }}</span>
                    <span class="text-[11px] font-semibold {{ str_ends_with($regionKey, '_esim') ? 'text-indigo-600' : ($regionKey === 'local_fast' ? 'text-orange-600' : 'text-slate-400') }}">· {{ match (true) { $regionKey === 'local_fast' => __('messages.sim_tab_hint_fast'), str_ends_with($regionKey, '_esim') => __('messages.sim_tab_hint_esim'), default => __('messages.sim_tab_hint_physical') } }}</span>
                </h4>
                <div class="overflow-x-auto -mx-5 sm:mx-0">
                    <table class="w-full text-sm text-start text-slate-600">
                        <thead class="text-xs text-slate-500 bg-slate-50 border-y border-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-start">#</th>
                                <th class="px-4 py-3 text-start">{{ __('messages.sim_name_ar') }} / {{ __('messages.sim_name_tr') }}</th>
                                <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_network') }}</th>
                                <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_type') }}</th>
                                <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_price') }}</th>
                                <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_supplier_code') }}</th>
                                <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_status') }}</th>
                                <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($packagesByTab->get($regionKey, collect()) as $package)
                                <tr wire:key="sim-manage-{{ $package->id }}" class="hover:bg-slate-50/60 transition {{ $package->is_active ? '' : 'opacity-60' }}">
                                    <td class="px-4 py-3 text-xs text-slate-400">{{ $package->sort_order }}</td>
                                    <td class="px-4 py-3 min-w-[240px]">
                                        <div class="font-bold text-slate-800 flex flex-wrap items-center gap-1.5" dir="rtl">
                                            {{ $package->name_ar }}
                                            @if($package->is_fast)
                                                <span class="inline-flex items-center gap-0.5 whitespace-nowrap rounded-full bg-gradient-to-r from-amber-400 to-orange-600 text-white text-[10px] font-black px-2 py-0.5">⚡ {{ __('messages.sim_fast_ribbon') }}</span>
                                            @endif
                                            @include('livewire.sim.partials.package-badge', ['badge' => $package->badge, 'size' => 'text-[10px] px-2 py-0.5'])
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5" dir="ltr">{{ $package->name_tr }}</div>
                                        <div class="text-[11px] text-slate-400 mt-1">{{ __('messages.sim_orders_count', ['count' => $package->orders_count]) }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-bold text-slate-700" dir="ltr">{{ $package->network_name ?: '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $package->isEsim() ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $package->isEsim() ? __('messages.sim_type_esim') : __('messages.sim_type_physical') }}
                                        </span>
                                        <div class="text-xs text-slate-500 mt-1" dir="ltr">{{ $package->data_gb }} GB · {{ $package->validity_days }}d</div>
                                    </td>
                                    <td class="px-4 py-3 font-black text-primary-600 whitespace-nowrap" dir="ltr">{{ SimPackage::formatPrice($package->price) }} ₺</td>
                                    <td class="px-4 py-3 text-xs whitespace-nowrap">
                                        <div class="font-mono text-slate-500" dir="ltr">{{ $package->supplier_code ?: '—' }}</div>
                                        @if($package->isApiReady())
                                            @if($package->isApiReadyFor('kontor86'))
                                                <span class="inline-block mt-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700" dir="ltr">🤖 API {{ $package->api_operator }}·{{ $package->api_tip }}·{{ $package->api_code }}</span>
                                            @endif
                                            @if($package->isApiReadyFor('telekomtl'))
                                                <span class="inline-block mt-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-teal-100 text-teal-800" dir="ltr">🤖 telekomtl {{ $package->telekomtl_operator }}·{{ $package->telekomtl_tip }}·{{ $package->telekomtl_code }}</span>
                                            @endif
                                            @if($package->preferred_supplier)
                                                <span class="inline-block mt-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-50 text-amber-800" title="{{ __('messages.sim_preferred_supplier') }}">⭐ <span dir="ltr">{{ $package->preferred_supplier }}</span></span>
                                            @endif
                                        @else
                                            <span class="inline-block mt-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500" title="{{ __('messages.sim_api_manual_hint') }}">
                                                ✋ {{ __('messages.sim_api_manual') }}@if($package->api_code) <span dir="ltr">· {{ $package->api_code }}</span>@endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <button type="button" wire:click="toggleActive({{ $package->id }})"
                                            class="text-xs font-bold px-2.5 py-1 rounded-full transition {{ $package->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }}">
                                            {{ $package->is_active ? __('messages.sim_active') : __('messages.sim_inactive') }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-data>
                                        <div class="flex gap-2">
                                            <button type="button" wire:click="edit({{ $package->id }})"
                                                class="text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                                {{ __('messages.sim_edit') }}
                                            </button>
                                            <button type="button"
                                                @click="Swal.fire({
                                                    title: @js(__('messages.sim_delete')),
                                                    text: @js(__('messages.sim_delete_confirm')),
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#ef4444',
                                                    cancelButtonColor: '#9ca3af',
                                                    confirmButtonText: @js(__('messages.sim_delete')),
                                                    cancelButtonText: @js(__('messages.sim_cancel'))
                                                }).then((r) => { if (r.isConfirmed) $wire.delete({{ $package->id }}) })"
                                                class="text-xs font-bold px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition">
                                                {{ __('messages.sim_delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-400 font-bold">{{ __('messages.sim_no_packages') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Add / edit modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-start sm:items-center justify-center p-4 overflow-y-auto" wire:key="sim-package-form">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl p-6 sm:p-8 my-8" @keydown.escape.window="$wire.cancel()">
                <h3 class="text-lg font-black text-slate-800 mb-6">
                    {{ $editingId ? __('messages.sim_edit_package') : __('messages.sim_add_package') }}
                </h3>

                <form wire:submit="save" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_region') }}</label>
                            <select wire:model="region" class="{{ $inputClass }}">
                                <option value="{{ SimPackage::REGION_LOCAL }}">{{ __('messages.sim_region_local') }}</option>
                                <option value="{{ SimPackage::REGION_EUROPE }}">{{ __('messages.sim_region_europe') }}</option>
                            </select>
                            @error('region') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_type') }}</label>
                            <select wire:model="simType" class="{{ $inputClass }}">
                                <option value="{{ SimPackage::TYPE_PHYSICAL }}">{{ __('messages.sim_type_physical') }}</option>
                                <option value="{{ SimPackage::TYPE_ESIM }}">{{ __('messages.sim_type_esim') }}</option>
                            </select>
                            @error('simType') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_network') }}</label>
                            <input type="text" dir="ltr" wire:model="networkName" maxlength="100" list="sim-known-networks"
                                placeholder="Tele CARD" class="{{ $inputClass }} font-bold">
                            <datalist id="sim-known-networks">
                                @foreach($knownNetworks as $network)
                                    <option value="{{ $network }}"></option>
                                @endforeach
                            </datalist>
                            <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.sim_network_hint') }}</p>
                            @error('networkName') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_data_gb') }}</label>
                            <input type="number" min="1" step="1" dir="ltr" wire:model="dataGb" class="{{ $inputClass }}">
                            @error('dataGb') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_validity_days') }}</label>
                            <input type="number" min="1" step="1" dir="ltr" wire:model="validityDays" class="{{ $inputClass }}">
                            @error('validityDays') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_price') }} (₺)</label>
                            <input type="number" min="1" step="0.01" dir="ltr" wire:model="price" class="{{ $inputClass }} font-bold">
                            @error('price') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_sort_order') }}</label>
                            <input type="number" min="0" step="1" dir="ltr" wire:model="sortOrder" class="{{ $inputClass }}">
                            @error('sortOrder') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_name_ar') }}</label>
                            <input type="text" dir="rtl" wire:model="nameAr" maxlength="255" class="{{ $inputClass }}">
                            @error('nameAr') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_name_tr') }}</label>
                            <input type="text" dir="ltr" wire:model="nameTr" maxlength="255" class="{{ $inputClass }}">
                            @error('nameTr') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <p class="sm:col-span-2 text-[11px] text-slate-400 -mt-2">{{ __('messages.sim_names_autofill_hint') }}</p>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_description_ar') }}</label>
                            <textarea dir="rtl" rows="3" wire:model="descriptionAr" maxlength="1000" class="{{ $inputClass }}"></textarea>
                            @error('descriptionAr') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_description_tr') }}</label>
                            <textarea dir="ltr" rows="3" wire:model="descriptionTr" maxlength="1000" class="{{ $inputClass }}"></textarea>
                            @error('descriptionTr') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.sim_supplier_code') }}</label>
                            <input type="text" dir="ltr" wire:model.live.debounce.500ms="supplierCode" maxlength="255" placeholder="FİZİKSEL Turkiye_5gb_30gun" class="{{ $inputClass }} font-mono">
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">{{ __('messages.sim_supplier_code_hint') }}</p>
                            @error('supplierCode') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer select-none py-2">
                            <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-5 h-5">
                            <span class="text-sm font-bold text-slate-700">{{ __('messages.sim_active_for_sale') }}</span>
                        </label>
                        {{-- An API code typed in the note field above: it is never sent, so offer to move it --}}
                        @if($supplierCodeProduct)
                            <div class="sm:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs font-bold text-amber-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <span>⚠️ {{ __('messages.sim_supplier_code_is_api', ['code' => $supplierCodeProduct['code'], 'product' => $supplierCodeProduct['name'], 'supplier' => $supplierCodeProduct['supplier']]) }}</span>
                                <button type="button" wire:click="useSupplierCodeAsKey" class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-black transition">
                                    {{ __('messages.sim_supplier_code_move') }}
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- «⚡ سريع · Hızlı»: own card colors, linked only to kontor86 HIZLI FİZİKSEL products --}}
                    <label class="flex items-start gap-3 cursor-pointer select-none rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-3">
                        <input type="checkbox" wire:model.live="isFast" class="mt-0.5 rounded border-amber-300 text-orange-500 focus:ring-orange-400 w-5 h-5">
                        <span>
                            <span class="block text-sm font-black text-orange-800">⚡ {{ __('messages.sim_fast_package') }}</span>
                            <span class="block text-[11px] text-orange-700/80 mt-0.5 leading-relaxed">{{ __('messages.sim_fast_package_hint') }}</span>
                        </span>
                    </label>

                    {{-- Colored label on the package card --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">{{ __('messages.sim_badge') }}</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="cursor-pointer rounded-full ring-2 ring-transparent ring-offset-2 has-[:checked]:ring-slate-800 transition">
                                <input type="radio" wire:model="badge" value="" class="sr-only">
                                <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-600 text-[11px] font-bold px-2.5 py-1">{{ __('messages.sim_badge_none') }}</span>
                            </label>
                            @foreach(SimPackage::BADGES as $badgeKey)
                                <label class="cursor-pointer rounded-full ring-2 ring-transparent ring-offset-2 has-[:checked]:ring-slate-800 transition">
                                    <input type="radio" wire:model="badge" value="{{ $badgeKey }}" class="sr-only">
                                    @include('livewire.sim.partials.package-badge', ['badge' => $badgeKey])
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">{{ __('messages.sim_badge_hint') }}</p>
                        @error('badge') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- How to fill in the supplier fields (open for a new package) --}}
                    @include('livewire.sim.partials.package-link-guide', ['open' => !$editingId])

                    {{-- Automatic loading: one key (operator / tip / code) per supplier --}}
                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                        <div class="text-sm font-black text-indigo-900">🤖 {{ __('messages.sim_api_section') }}</div>
                        <p class="text-[11px] text-indigo-700/80 mt-1 mb-3 leading-relaxed">{{ __('messages.sim_api_section_hint') }}</p>
                        <div class="flex flex-wrap items-baseline gap-x-2 mb-1.5">
                            <span class="text-[11px] font-black text-indigo-900" dir="ltr">kontor86</span>
                            <span class="text-[10px] text-indigo-700/80">{{ __('messages.sim_api_kontor_hint') }}</span>
                        </div>
                        @include('livewire.sim.partials.supplier-product-picker', ['supplier' => 'kontor86', 'pickModel' => 'pickKontor', 'groups' => $catalogue['kontor86'], 'tip' => $apiTip, 'code' => $apiCode, 'price' => $price, 'suggestion' => $suggestions['kontor86'] ?? null])
                        <div class="grid grid-cols-3 gap-3 mt-2" dir="ltr">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">{{ __('messages.sim_api_operator') }}</label>
                                <input type="text" wire:model="apiOperator" maxlength="30" placeholder="operator" class="{{ $inputClass }} font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">{{ __('messages.sim_api_tip') }}</label>
                                <input type="text" wire:model="apiTip" maxlength="30" placeholder="tip" class="{{ $inputClass }} font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">{{ __('messages.sim_api_code') }}</label>
                                <input type="text" wire:model="apiCode" maxlength="30" placeholder="API" class="{{ $inputClass }} font-mono">
                            </div>
                        </div>
                        @error('apiOperator') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                        @error('apiTip') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                        @error('apiCode') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror

                        <div class="flex flex-wrap items-baseline gap-x-2 mt-4 mb-1.5">
                            <span class="text-[11px] font-black text-teal-800" dir="ltr">telekomtl</span>
                            <span class="text-[10px] text-teal-700/80">{{ __('messages.sim_api_telekomtl_hint') }}</span>
                        </div>
                        @include('livewire.sim.partials.supplier-product-picker', ['supplier' => 'telekomtl', 'pickModel' => 'pickTk', 'groups' => $catalogue['telekomtl'], 'tip' => $tkTip, 'code' => $tkCode, 'price' => $price, 'suggestion' => $suggestions['telekomtl'] ?? null])
                        <div class="grid grid-cols-3 gap-3 mt-2" dir="ltr">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">{{ __('messages.sim_api_operator') }}</label>
                                <input type="text" wire:model="tkOperator" maxlength="30" placeholder="operator" class="{{ $inputClass }} font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">{{ __('messages.sim_api_tip') }}</label>
                                <input type="text" wire:model="tkTip" maxlength="30" placeholder="tip" class="{{ $inputClass }} font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">{{ __('messages.sim_api_code') }}</label>
                                <input type="text" wire:model="tkCode" maxlength="30" placeholder="API" class="{{ $inputClass }} font-mono">
                            </div>
                        </div>
                        @error('tkOperator') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                        @error('tkTip') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                        @error('tkCode') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror

                        <div class="mt-4">
                            <label class="block text-[11px] font-black text-indigo-900 mb-1">{{ __('messages.sim_preferred_supplier') }}</label>
                            <select wire:model="preferredSupplier" class="{{ $inputClass }}">
                                <option value="">{{ __('messages.sim_preferred_supplier_default', ['supplier' => \App\Services\KontorApiService::primarySupplier()]) }}</option>
                                @foreach(\App\Services\KontorApiService::SUPPLIERS as $supplierOption)
                                    <option value="{{ $supplierOption }}">{{ $supplierOption }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-indigo-700/80 mt-1">{{ __('messages.sim_preferred_supplier_hint') }}</p>
                            @error('preferredSupplier') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="cancel"
                            class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                            {{ __('messages.sim_cancel') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="px-8 py-2.5 rounded-xl text-sm font-black text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition disabled:opacity-60">
                            {{ __('messages.sim_save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
