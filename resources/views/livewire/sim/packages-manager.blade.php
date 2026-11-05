@use('App\Models\SimPackage')
@php
    $regionLabels = [
        SimPackage::REGION_LOCAL => '🇹🇷 ' . __('messages.sim_region_local'),
        SimPackage::REGION_EUROPE => '🇪🇺 ' . __('messages.sim_region_europe'),
    ];
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

        @foreach($regionLabels as $regionKey => $regionLabel)
            <div class="mb-8 last:mb-0">
                <h4 class="text-sm font-black text-slate-600 mb-3">{{ $regionLabel }}</h4>
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
                            @forelse($packagesByRegion->get($regionKey, collect()) as $package)
                                <tr wire:key="sim-manage-{{ $package->id }}" class="hover:bg-slate-50/60 transition {{ $package->is_active ? '' : 'opacity-60' }}">
                                    <td class="px-4 py-3 text-xs text-slate-400">{{ $package->sort_order }}</td>
                                    <td class="px-4 py-3 min-w-[240px]">
                                        <div class="font-bold text-slate-800" dir="rtl">{{ $package->name_ar }}</div>
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
                                    <td class="px-4 py-3 text-xs font-mono text-slate-500 whitespace-nowrap" dir="ltr">{{ $package->supplier_code ?: '—' }}</td>
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
                            <input type="text" dir="ltr" wire:model="supplierCode" maxlength="255" placeholder="Turkiye_5gb_30gun" class="{{ $inputClass }} font-mono">
                            @error('supplierCode') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer select-none py-2">
                            <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-5 h-5">
                            <span class="text-sm font-bold text-slate-700">{{ __('messages.sim_active_for_sale') }}</span>
                        </label>
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
