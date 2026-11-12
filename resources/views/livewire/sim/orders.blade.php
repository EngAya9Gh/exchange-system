@use('App\Models\SimPackage')
@use('App\Models\SimOrder')

<div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-5 sm:p-8" @if($hasOpenApiOrders) wire:poll.15s @endif>
    {{-- Title on one side, filters on the other; they wrap only when the row is too narrow --}}
    @php $filterClass = 'rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm text-sm py-2'; @endphp
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 mb-6">
        <div class="min-w-0">
            <h3 class="text-xl font-black text-slate-800 flex flex-wrap items-center gap-2">
                {{ $canManage ? __('messages.sim_orders') : __('messages.sim_my_orders') }}
                @if($canManage && $pendingCount > 0)
                    <span class="bg-amber-100 text-amber-800 text-xs font-black px-2.5 py-1 rounded-full">
                        {{ __('messages.sim_pending_count', ['count' => $pendingCount]) }}
                    </span>
                @endif
            </h3>
            @if($supplierBalance !== null)
                <div class="mt-1.5">@include('livewire.sim.partials.supplier-balance')</div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <svg class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="search" wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('messages.sim_search_placeholder') }}"
                    class="{{ $filterClass }} w-full ps-9">
            </div>
            <select wire:model.live="statusFilter" aria-label="{{ __('messages.sim_status') }}" class="{{ $filterClass }} flex-1 min-w-0 sm:flex-none sm:w-36">
                <option value="all">{{ __('messages.sim_filter_all') }}</option>
                <option value="{{ SimOrder::STATUS_PENDING }}">{{ __('messages.sim_status_pending') }}</option>
                <option value="{{ SimOrder::STATUS_COMPLETED }}">{{ __('messages.sim_status_completed') }}</option>
                <option value="{{ SimOrder::STATUS_REJECTED }}">{{ __('messages.sim_status_rejected') }}</option>
            </select>
            <select wire:model.live="period" aria-label="{{ __('messages.sim_period') }}" class="{{ $filterClass }} flex-1 min-w-0 sm:flex-none sm:w-36">
                @foreach(\App\Livewire\Sim\Orders::PERIODS as $periodKey)
                    <option value="{{ $periodKey }}">{{ __('messages.sim_period_' . $periodKey) }}</option>
                @endforeach
            </select>
            @if($period === 'custom')
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <label class="text-xs font-bold text-slate-500 shrink-0" for="sim-date-from">{{ __('messages.sim_date_from') }}</label>
                    <input id="sim-date-from" type="date" wire:model.live="dateFrom" dir="ltr" class="{{ $filterClass }} w-full sm:w-36">
                    <label class="text-xs font-bold text-slate-500 shrink-0" for="sim-date-to">{{ __('messages.sim_date_to') }}</label>
                    <input id="sim-date-to" type="date" wire:model.live="dateTo" dir="ltr" class="{{ $filterClass }} w-full sm:w-36">
                </div>
            @endif
        </div>
    </div>

    @include('livewire.sim.partials.order-stats')

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
                            @elseif($order->status === SimOrder::STATUS_COMPLETED && in_array($order->api_status, SimOrder::API_OPEN_STATES, true))
                                {{-- Automatic load still running at the supplier --}}
                                <span class="inline-flex items-center gap-1.5 bg-sky-100 text-sky-800 text-xs font-bold px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                                    {{ __('messages.sim_status_loading') }}
                                </span>
                            @elseif($order->status === SimOrder::STATUS_COMPLETED && $order->api_status === SimOrder::API_SUCCESS)
                                <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    {{ __('messages.sim_status_loaded') }}
                                </span>
                            @elseif($order->status === SimOrder::STATUS_COMPLETED)
                                <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-full">{{ __('messages.sim_status_completed') }}</span>
                            @else
                                <span class="bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-1 rounded-full">{{ __('messages.sim_status_rejected') }}</span>
                            @endif
                            @if($canManage && $order->processedBy)
                                <div class="text-[11px] text-slate-400 mt-1.5">{{ $order->processedBy->name }}</div>
                            @endif

                            {{-- Automatic loading through kontor86 (team only) --}}
                            @if($canManage)
                                @php
                                    $apiBadge = match ($order->api_status) {
                                        SimOrder::API_SUCCESS => ['bg-indigo-100 text-indigo-700', '🤖 ' . __('messages.sim_api_state_success')],
                                        SimOrder::API_PROCESSING => ['bg-sky-100 text-sky-700', '🤖 ' . __('messages.sim_api_state_processing')],
                                        SimOrder::API_UNKNOWN => ['bg-amber-100 text-amber-800', '⚠️ ' . __('messages.sim_api_state_unknown')],
                                        SimOrder::API_FAILED => ['bg-rose-100 text-rose-700', '❌ ' . __('messages.sim_api_state_failed')],
                                        default => ['bg-slate-100 text-slate-500', '✋ ' . __('messages.sim_api_manual')],
                                    };
                                @endphp
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full {{ $apiBadge[0] }}" title="{{ $order->api_message }}">{{ $apiBadge[1] }}</span>
                                    @if(in_array($order->api_status, SimOrder::API_OPEN_STATES, true))
                                        <button type="button" wire:click="checkSupplier({{ $order->id }})" wire:loading.attr="disabled" wire:target="checkSupplier({{ $order->id }})"
                                            class="text-[10px] font-black px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition disabled:opacity-50">
                                            <span wire:loading.remove wire:target="checkSupplier({{ $order->id }})">{{ __('messages.sim_api_check_now') }}</span>
                                            <span wire:loading wire:target="checkSupplier({{ $order->id }})">…</span>
                                        </button>
                                    @endif
                                </div>
                                @if($order->api_status === SimOrder::API_FAILED && $order->api_message)
                                    <div class="text-[10px] text-rose-600 mt-1 max-w-[200px] whitespace-normal">{{ $order->api_message }}</div>
                                @endif
                                @if($order->api_cost !== null && (float) $order->api_cost > 0 && $order->status !== SimOrder::STATUS_REJECTED)
                                    @php $orderProfit = $order->profit(); @endphp
                                    <dl class="mt-2 grid grid-cols-[auto_auto] gap-x-3 gap-y-0.5 text-[11px] w-max">
                                        <dt class="text-slate-500">{{ __('messages.sim_api_cost') }}</dt>
                                        <dd class="font-bold text-slate-700"><span dir="ltr">{{ SimPackage::formatPrice($order->api_cost) }} ₺</span></dd>
                                        @if($orderProfit !== null)
                                            <dt class="text-slate-500">{{ $orderProfit >= 0 ? __('messages.sim_api_profit') : __('messages.sim_api_loss') }}</dt>
                                            <dd class="font-black {{ $orderProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}"><span dir="ltr">{{ SimPackage::formatPrice(abs($orderProfit)) }} ₺</span></dd>
                                        @endif
                                    </dl>
                                @endif
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
