@use('App\Models\SimPackage')
@use('App\Models\SimOrder')

<div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
            {{ $canManage ? __('messages.sim_orders') : __('messages.sim_my_orders') }}
            @if($canManage && $pendingCount > 0)
                <span class="bg-amber-100 text-amber-800 text-xs font-black px-2.5 py-1 rounded-full">
                    {{ __('messages.sim_pending_count', ['count' => $pendingCount]) }}
                </span>
            @endif
            @if($supplierBalance !== null)
                @if($supplierBalance['ok'])
                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-bold px-2.5 py-1 rounded-full">
                        {{ __('messages.sim_supplier_balance') }}: <span class="font-black" dir="ltr">{{ number_format($supplierBalance['balance'], 2) }} ₺</span>
                    </span>
                @else
                    <span class="bg-rose-50 text-rose-700 border border-rose-100 text-xs font-bold px-2.5 py-1 rounded-full" title="{{ $supplierBalance['error'] }}">
                        {{ __('messages.sim_supplier_not_connected') }} <span class="font-mono text-[10px] opacity-70" dir="ltr">({{ $supplierBalance['error'] }})</span>
                    </span>
                @endif
            @endif
        </h3>

        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <input type="search" wire:model.live.debounce.400ms="search"
                placeholder="{{ __('messages.sim_search_placeholder') }}"
                class="rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm text-sm w-full sm:w-72">
            <select wire:model.live="statusFilter"
                class="rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm text-sm w-full sm:w-48">
                <option value="all">{{ __('messages.sim_filter_all') }}</option>
                <option value="{{ SimOrder::STATUS_PENDING }}">{{ __('messages.sim_status_pending') }}</option>
                <option value="{{ SimOrder::STATUS_COMPLETED }}">{{ __('messages.sim_status_completed') }}</option>
                <option value="{{ SimOrder::STATUS_REJECTED }}">{{ __('messages.sim_status_rejected') }}</option>
            </select>
        </div>
    </div>

    @if (session()->has('sim_success'))
        <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 font-bold">{{ session('sim_success') }}</div>
    @endif
    @if (session()->has('sim_error'))
        <div class="mb-4 p-4 bg-rose-50 text-rose-700 rounded-xl border border-rose-100 font-bold">{{ session('sim_error') }}</div>
    @endif

    <div class="overflow-x-auto -mx-5 sm:mx-0">
        <table class="w-full text-sm text-start text-slate-600">
            <thead class="text-xs text-slate-500 bg-slate-50 border-y border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_order_number') }}</th>
                    @if($canManage)
                        <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_ordered_by') }}</th>
                    @endif
                    <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_package') }}</th>
                    <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_phone') }}</th>
                    <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_price') }}</th>
                    <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_date') }}</th>
                    <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_status') }}</th>
                    @if($canManage)
                        <th class="px-4 py-3 text-start whitespace-nowrap">{{ __('messages.sim_actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr wire:key="sim-order-{{ $order->id }}" class="hover:bg-slate-50/60 transition align-top">
                        <td class="px-4 py-4 font-mono text-xs font-bold text-slate-800 whitespace-nowrap" dir="ltr">{{ $order->order_number }}</td>

                        @if($canManage)
                            <td class="px-4 py-4 font-bold text-slate-700 whitespace-nowrap">{{ $order->user?->name ?? '-' }}</td>
                        @endif

                        <td class="px-4 py-4 min-w-[220px]">
                            <div class="font-bold text-slate-800">{{ $order->localized_package_name }}</div>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                @if($order->network_name)
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-slate-800 text-white" dir="ltr" title="{{ __('messages.sim_network') }}">{{ $order->network_name }}</span>
                                @endif
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $order->isEsim() ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $order->isEsim() ? __('messages.sim_type_esim') : __('messages.sim_type_physical') }}
                                </span>
                                @if($canManage && $order->package?->supplier_code)
                                    <span class="text-[10px] font-mono text-slate-500 bg-slate-100 px-2 py-0.5 rounded" dir="ltr" title="{{ __('messages.sim_supplier_code') }}">{{ $order->package->supplier_code }}</span>
                                @endif
                            </div>
                            @if($order->customer_name)
                                <div class="text-xs text-slate-500 mt-1.5">{{ __('messages.sim_customer_name_short') }}: <span class="font-bold">{{ $order->customer_name }}</span></div>
                            @endif
                            @if($order->notes)
                                <div class="text-xs text-slate-500 mt-1">{{ __('messages.sim_notes_short') }}: {{ $order->notes }}</div>
                            @endif
                            @if($order->admin_notes)
                                <div class="text-xs mt-1.5 px-2 py-1 rounded-lg inline-block {{ $order->status === SimOrder::STATUS_REJECTED ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ __('messages.sim_admin_note') }}: {{ $order->admin_notes }}
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2" x-data="{ copied: false }">
                                <span class="font-mono font-bold text-slate-800" dir="ltr">{{ $order->phone_number }}</span>
                                @if($canManage)
                                    <button type="button" title="{{ __('messages.sim_copy') }}"
                                        @click="navigator.clipboard.writeText(@js($order->phone_number)); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="text-slate-400 hover:text-primary-600 transition">
                                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                        <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-4 font-black text-slate-800 whitespace-nowrap" dir="ltr">{{ SimPackage::formatPrice($order->price) }} ₺</td>

                        <td class="px-4 py-4 text-xs whitespace-nowrap" dir="ltr">
                            {{ $order->created_at->format('Y-m-d H:i') }}
                            @if($order->processed_at)
                                <div class="text-slate-400 mt-1">✓ {{ $order->processed_at->format('Y-m-d H:i') }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap">
                            @if($order->status === SimOrder::STATUS_PENDING)
                                <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1 rounded-full">{{ __('messages.sim_status_pending') }}</span>
                            @elseif($order->status === SimOrder::STATUS_COMPLETED)
                                <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-full">{{ __('messages.sim_status_completed') }}</span>
                            @else
                                <span class="bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-1 rounded-full">{{ __('messages.sim_status_rejected') }}</span>
                            @endif
                            @if($canManage && $order->processedBy)
                                <div class="text-[11px] text-slate-400 mt-1.5">{{ $order->processedBy->name }}</div>
                            @endif
                        </td>

                        @if($canManage)
                            <td class="px-4 py-4 whitespace-nowrap" x-data>
                                @if($order->status !== SimOrder::STATUS_REJECTED)
                                    <div class="flex gap-2">
                                        {{-- Legacy orders placed before instant activation can still be confirmed --}}
                                        @if($order->isPending())
                                        <button type="button"
                                            @click="Swal.fire({
                                                title: @js(__('messages.sim_complete_confirm_title')),
                                                text: @js($order->localized_package_name . ' — ' . $order->phone_number),
                                                input: 'text',
                                                inputPlaceholder: @js(__('messages.sim_complete_note_placeholder')),
                                                icon: 'question',
                                                showCancelButton: true,
                                                confirmButtonColor: '#10b981',
                                                cancelButtonColor: '#9ca3af',
                                                confirmButtonText: @js(__('messages.sim_mark_completed')),
                                                cancelButtonText: @js(__('messages.sim_cancel'))
                                            }).then((r) => { if (r.isConfirmed) $wire.complete({{ $order->id }}, r.value || '') })"
                                            wire:loading.attr="disabled"
                                            class="text-white bg-emerald-500 hover:bg-emerald-600 px-3 py-1.5 rounded-lg shadow-sm text-xs font-bold transition disabled:opacity-60">
                                            {{ __('messages.sim_mark_completed') }}
                                        </button>
                                        @endif
                                        {{-- Activated orders can be cancelled & refunded if loading on the supplier panel fails --}}
                                        <button type="button"
                                            @click="Swal.fire({
                                                title: @js($order->isPending() ? __('messages.sim_reject_confirm_title') : __('messages.sim_cancel_refund_confirm_title')),
                                                text: @js(__('messages.sim_reject_confirm_text', ['amount' => SimPackage::formatPrice($order->charged_amount)])),
                                                input: 'textarea',
                                                inputPlaceholder: @js(__('messages.sim_reject_reason_placeholder')),
                                                inputValidator: (v) => (!v || !v.trim()) ? @js(__('messages.sim_reject_reason_required')) : undefined,
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#ef4444',
                                                cancelButtonColor: '#9ca3af',
                                                confirmButtonText: @js($order->isPending() ? __('messages.sim_reject') : __('messages.sim_cancel_refund')),
                                                cancelButtonText: @js(__('messages.sim_cancel'))
                                            }).then((r) => { if (r.isConfirmed) $wire.reject({{ $order->id }}, r.value) })"
                                            wire:loading.attr="disabled"
                                            class="text-white bg-rose-500 hover:bg-rose-600 px-3 py-1.5 rounded-lg shadow-sm text-xs font-bold transition disabled:opacity-60">
                                            {{ $order->isPending() ? __('messages.sim_reject') : __('messages.sim_cancel_refund') }}
                                        </button>
                                    </div>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 8 : 6 }}" class="px-4 py-12 text-center text-slate-400 font-bold">{{ __('messages.sim_no_orders') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="pt-4 border-t border-slate-100 mt-2">
            {{ $orders->links() }}
        </div>
    @endif
</div>
