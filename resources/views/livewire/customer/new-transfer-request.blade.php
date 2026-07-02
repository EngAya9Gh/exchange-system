<div x-data="{
    confirmTransfer() {
        Swal.fire({
            title: '{{ __("messages.confirm_send_transfer_title") }}',
            text: '{{ __("messages.confirm_send_transfer_text") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#cbd5e1',
            confirmButtonText: '{{ __("messages.yes_send_request") }}',
            cancelButtonText: '{{ __("messages.cancel_btn") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.submitRequest();
            }
        });
    }
}" class="bg-white rounded-[28px] shadow-soft border border-slate-50 p-8" wire:init="autoSyncRates">
    <h3 class="text-xl font-bold text-slate-800 border-b border-slate-50 pb-4 mb-6">
        {{ __('messages.new_financial_transfer_request') }}</h3>

    @if (session()->has('success'))
        <div class="mb-8 p-4 bg-emerald-50 text-emerald-800 font-bold rounded-2xl flex items-center">
            <svg class="w-5 h-5 ml-2 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <form x-on:submit.prevent="confirmTransfer" class="space-y-6">
        <!-- Recipient Info -->
        <div class="bg-slate-50/50 p-6 rounded-[24px] space-y-5">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                {{ __('messages.target_recipient') }}</h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="recipient_name"
                        class="block text-sm font-bold text-slate-700 mb-2">{{ __('messages.full_recipient_name') }}</label>
                    <input wire:model="recipient_name" id="recipient_name" type="text"
                        class="w-full bg-white border-none text-slate-800 font-semibold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition"
                        required />
                    <x-input-error :messages="$errors->get('recipient_name')" class="mt-2 text-rose-500 text-xs" />
                </div>

                <div>
                    <label for="recipient_phone"
                        class="block text-sm font-bold text-slate-700 mb-2">{{ __('messages.recipient_phone_number') }}</label>
                    <input wire:model="recipient_phone" id="recipient_phone" type="text"
                        class="w-full bg-white border-none text-slate-800 font-semibold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition"
                        placeholder="+20..." required />
                    <x-input-error :messages="$errors->get('recipient_phone')" class="mt-2 text-rose-500 text-xs" />
                </div>

                <div>
                    <label for="address"
                        class="block text-sm font-bold text-slate-700 mb-2">{{ __('messages.address') }}</label>
                    <input wire:model="address" id="address" type="text"
                        class="w-full bg-white border-none text-slate-800 font-semibold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition"
                        placeholder="{{ __('messages.address_example') }}" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2 text-rose-500 text-xs" />
                </div>
            </div>
        </div>

        <hr class="border-slate-50 my-2">

        <!-- Financial Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Region & Branch -->
            <div class="md:col-span-2">
                <!-- Amount Inputs -->
                <div class="bg-slate-50/50 p-6 rounded-[24px] grid grid-cols-1 sm:grid-cols-4 gap-5">
                    <div class="sm:col-span-1" x-data="{
                        formatted: '',
                        formatNumber(val) {
                            if (!val && val !== 0 && val !== '0') return '';
                            let str = val.toString().replace(/[^0-9.]/g, '');
                            let parts = str.split('.');
                            let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                            return parts.length > 1 ? intPart + '.' + parts[1] : intPart;
                        }
                    }" x-init="
                        formatted = formatNumber($wire.amount);
                        $watch('$wire.amount', val => { if(document.activeElement !== $refs.input) formatted = formatNumber(val); });
                    ">
                        <label for="amount" class="block text-sm font-bold text-slate-700 mb-2">{{ __('messages.desired_transfer_amount') }}</label>
                        <input x-ref="hidden" type="hidden" wire:model.live.debounce.500ms="amount">
                        <input x-ref="input" type="text" inputmode="decimal" x-model="formatted"
                            @input="
                                let val = $event.target.value.replace(/[^0-9.]/g, '');
                                let parts = val.split('.');
                                if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
                                val = parts.join('.');
                                $refs.hidden.value = val;
                                $refs.hidden.dispatchEvent(new Event('input', { bubbles: true }));
                                let displayInt = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                formatted = parts.length > 1 ? displayInt + '.' + parts[1] : displayInt;
                            "
                            id="amount"
                            class="w-full bg-white border-none text-primary-600 font-black text-xl sm:text-2xl rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3 shadow-sm transition"
                            required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2 text-rose-500 text-xs" />
                    </div>

                    <div class="sm:col-span-1" x-data="{
                        formatted: '',
                        formatNumber(val) {
                            if (!val && val !== 0 && val !== '0') return '';
                            let str = val.toString().replace(/[^0-9.]/g, '');
                            let parts = str.split('.');
                            let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                            return parts.length > 1 ? intPart + '.' + parts[1] : intPart;
                        }
                    }" x-init="
                        formatted = formatNumber($wire.received_amount);
                        $watch('$wire.received_amount', val => { if(document.activeElement !== $refs.input) formatted = formatNumber(val); });
                    ">
                        <label for="received_amount" class="block text-sm font-bold text-slate-700 mb-2">{{ __('messages.received_amount_in_egp') }}</label>
                        <input x-ref="hidden" type="hidden" wire:model.live.debounce.500ms="received_amount">
                        <input x-ref="input" type="text" inputmode="decimal" x-model="formatted"
                            @input="
                                let val = $event.target.value.replace(/[^0-9.]/g, '');
                                let parts = val.split('.');
                                if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
                                val = parts.join('.');
                                $refs.hidden.value = val;
                                $refs.hidden.dispatchEvent(new Event('input', { bubbles: true }));
                                let displayInt = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                formatted = parts.length > 1 ? displayInt + '.' + parts[1] : displayInt;
                            "
                            id="received_amount"
                            class="w-full bg-white border-none text-emerald-600 font-black text-xl sm:text-2xl rounded-xl focus:ring-2 focus:ring-emerald-500 px-4 py-3 shadow-sm transition"
                            required />
                        <x-input-error :messages="$errors->get('received_amount')" class="mt-2 text-rose-500 text-xs" />
                    </div>

                    <div>
                        <label for="currency" class="block text-sm font-bold text-slate-700 mb-2">{{ __('messages.currency_label') }}</label>
                        <select wire:model.live="currency" id="currency"
                            class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 pr-4 pl-10 py-3 shadow-sm transition bg-left">
                            <option value="TRY">{{ __('messages.try_lira') }}</option>
                            <option value="USD">{{ __('messages.usd_dollar') }}</option>
                            <option value="EUR">{{ __('messages.eur_euro') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('currency')" class="mt-2 text-rose-500 text-xs" />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">{{ __('messages.exchange_rate_label') }}</label>
                        <input wire:model="exchange_rate" type="text"
                            class="w-full bg-slate-100/50 border-none text-slate-500 font-bold text-xl rounded-xl focus:ring-0 px-4 py-3 shadow-sm cursor-not-allowed"
                            disabled />
                    </div>
                </div>
            </div>

            <!-- Summary Box -->
            <div
                class="bg-gradient-to-br from-primary-50 to-rose-50 border border-primary-100/50 rounded-[24px] p-6 flex flex-col justify-between shadow-sm relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-white/40 blur-xl"></div>

                <div class="space-y-4 relative z-10">
                    <h4
                        class="text-[11px] font-bold text-primary-500 uppercase tracking-widest border-b border-primary-200/50 pb-2 mb-2">
                        {{ __('messages.estimated_calculation_details') }}</h4>

                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-bold">{{ __('messages.exchange_rate_egp') }}</span>
                        <span class="font-black text-slate-800">{{ number_format($exchange_rate, 4) }}</span>
                    </div>

                    <div class="flex justify-between items-center text-sm pt-3 border-t border-primary-200/50">
                        <span class="text-slate-500 font-bold">{{ __('messages.estimated_commission') }}</span>
                        <span class="font-black text-rose-500">{{ number_format($commission, 2) }}
                            {{ $currency }}</span>
                    </div>


                </div>

                <div class="pt-6 relative z-10">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-slate-500">{{ __('messages.total_cost_on_you') }}</span>
                        <span class="font-black text-slate-800 text-2xl">{{ number_format($total_to_pay, 2) }} <span
                                class="text-sm text-slate-500">{{ $currency }}</span></span>
                    </div>

                    @php
                        $user = auth()->user();
                        $availableCredit = $user->has_unlimited_balance ? PHP_FLOAT_MAX : ($user->balance + $user->balance_limit);
                        $cannotAfford = $total_to_pay > $availableCredit;
                    @endphp

                    <div
                        class="flex justify-between items-center mb-4 p-2 rounded-lg {{ $cannotAfford ? 'bg-rose-100 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                        <span class="text-xs font-bold">{{ __('messages.available_for_transfer_with_limit') }}</span>
                        <span class="font-black text-lg">
                            @if($user->has_unlimited_balance)
                                {{ __('messages.unlimited') }}
                            @else
                                {{ number_format($availableCredit, 2) }} <span class="text-xs">TRY</span>
                            @endif
                        </span>
                    </div>

                    <button type="submit" @if($cannotAfford) disabled @endif wire:loading.attr="disabled"
                        wire:target="submitRequest"
                        class="w-full py-4 bg-gradient-to-r from-primary-600 to-rose-600 hover:from-primary-700 hover:to-rose-700 text-white rounded-xl font-black text-lg shadow-soft transition-transform hover:-translate-y-1 flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                        <span wire:loading.remove wire:target="submitRequest" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            {{ __('messages.send_request_for_review') }}
                        </span>
                        <span wire:loading wire:target="submitRequest" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('messages.sending_and_processing') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>