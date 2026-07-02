<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 font-cairo">{{ __('messages.quick_balance_management') }}</h2>
    </div>

    <!-- Filters -->
    <x-card class="mb-6 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <x-text-input wire:model.live="searchQuery" placeholder="{{ __('messages.search_customer') }}" class="w-full sm:w-1/2" />
        </div>
    </x-card>

    @if(session('success'))
        <div class="mb-4 bg-success-50 text-success-700 p-4 rounded-lg border border-success-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Balances Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-sm text-center text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-white/50 backdrop-blur-sm border-b border-white/40">
                <tr>
                    <th class="px-6 py-3 text-center">{{ __('messages.user_name') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.phone_number') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.actual_balance') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.credit_limit') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                        <td class="px-6 py-4 font-bold font-mono text-gray-500" dir="ltr">{{ $user->phone }}</td>
                        <td class="px-6 py-4 font-bold text-green-600">{{ number_format($user->balance, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($user->has_unlimited_balance)
                                <span class="text-gray-500 text-xs font-bold">{{ __('messages.unlimited_limit') }}</span>
                            @else
                                <span class="text-red-600 font-bold">{{ number_format($user->balance_limit, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button wire:click="openPaymentModal({{ $user->id }})" class="text-white font-bold text-xs bg-blue-500 hover:bg-blue-600 px-3 py-1.5 rounded-lg shadow-sm transition">
                                {{ __('messages.record_payment') }}
                            </button>
                            <button wire:click="openDepositModal({{ $user->id }})" class="text-white font-bold text-xs bg-emerald-500 hover:bg-emerald-600 px-3 py-1.5 rounded-lg shadow-sm transition">
                                {{ __('messages.deposit_withdraw') }}
                            </button>
                            <button wire:click="openSettingsModal({{ $user->id }})" class="text-primary-600 hover:text-white font-bold text-xs border border-primary-500 hover:bg-primary-600 px-3 py-1.5 rounded-lg shadow-sm transition">
                                {{ __('messages.account_settings') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">{{ __('messages.no_users_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
    </x-card>

    @if($showDepositModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity" wire:click="closeModals"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[24px] text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-3 text-center sm:mt-0 sm:text-start w-full">
                            <h3 class="text-xl leading-6 font-black text-gray-900 mb-2" id="modal-title">{{ __('messages.deposit_withdraw_balance') }}</h3>
                            <p class="text-sm text-gray-500 mb-6">{{ __('messages.customer') }}: <span class="font-bold text-gray-900">{{ $manageUserName }}</span> | {{ __('messages.current_balance') }}: <span class="font-bold text-green-600">{{ number_format($manageUserBalance, 2) }}</span></p>
                            
                            <div class="mb-4 text-start">
                                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.amount_to_add') }}</label>
                                <input type="number" wire:model="depositAmount" step="0.01" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 font-bold text-lg" placeholder="500">
                                @error('depositAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end gap-2 border-t border-gray-100">
                        <button wire:click="closeModals" class="bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-xl shadow-sm transition">{{ __('messages.cancel') }}</button>
                        <button wire:click="confirmDeposit" class="bg-emerald-500 text-white px-6 py-2 text-sm font-bold rounded-xl shadow-md hover:bg-emerald-600 transition hover:-translate-y-0.5">{{ __('messages.confirm_operation') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Payment Modal -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity" wire:click="closeModals"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[24px] text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-3 text-center sm:mt-0 sm:text-start w-full">
                            <h3 class="text-xl leading-6 font-black text-gray-900 mb-2" id="modal-title">{{ __('messages.record_payment') }}</h3>
                            <p class="text-sm text-gray-500 mb-6">{{ __('messages.customer') }}: <span class="font-bold text-gray-900">{{ $manageUserName }}</span> | {{ __('messages.current_balance') }}: <span class="font-bold text-green-600">{{ number_format($manageUserBalance, 2) }}</span></p>
                            
                            <div class="mb-4 text-start">
                                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.payment_amount') }}</label>
                                <input type="number" wire:model="paymentAmount" step="0.01" min="0.01" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold text-lg" placeholder="500">
                                @error('paymentAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-4 text-start">
                                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.payment_method') }}</label>
                                <select wire:model="paymentMethod" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold">
                                    <option value="cash">{{ __('messages.cash') }}</option>
                                    <option value="bank_transfer">{{ __('messages.bank_transfer') }}</option>
                                    <option value="sham_cash">{{ __('messages.method_sham') }}</option>
                                    <option value="uption">{{ __('messages.method_uption') }}</option>
                                    <option value="twasul">{{ __('messages.method_twasul') }}</option>
                                </select>
                                @error('paymentMethod') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-4 text-start">
                                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.payment_notes') }}</label>
                                <textarea wire:model="paymentNotes" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" rows="3" placeholder="{{ __('messages.optional_notes') }}"></textarea>
                                @error('paymentNotes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end gap-2 border-t border-gray-100">
                        <button wire:click="closeModals" class="bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-xl shadow-sm transition">{{ __('messages.cancel') }}</button>
                        <button wire:click="confirmPayment" class="bg-blue-500 text-white px-6 py-2 text-sm font-bold rounded-xl shadow-md hover:bg-blue-600 transition hover:-translate-y-0.5">{{ __('messages.confirm_payment') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif


    <!-- Settings Modal -->
    @if($showSettingsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity" wire:click="closeModals"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[24px] text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-xl leading-6 font-black text-gray-900 mb-6">{{ __('messages.account_settings_for') }}{{ $manageUserName }}</h3>
                        <div class="space-y-6 text-start">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.absolute_balance_label') }}</label>
                                <input type="number" wire:model="absoluteBalance" step="0.01" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-bold">
                                @error('absoluteBalance') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                <label class="flex items-center gap-2 text-sm text-gray-900 font-bold mb-4 cursor-pointer">
                                    <input type="checkbox" wire:model.live="editHasUnlimited" class="rounded border-blue-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    {{ __('messages.make_unlimited_limit') }}
                                </label>

                                @if(!$editHasUnlimited)
                                    <div class="pt-2 border-t border-blue-100/50">
                                        <label class="block text-xs font-bold text-gray-700 mb-2">{{ __('messages.max_debt_limit') }}</label>
                                        <input type="number" wire:model="editBalanceLimit" step="0.01" min="0" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold text-red-600" placeholder="5000">
                                        @error('editBalanceLimit') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end gap-2 border-t border-gray-100">
                        <button wire:click="closeModals" class="bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-xl shadow-sm transition">{{ __('messages.cancel') }}</button>
                        <button wire:click="confirmSettings" class="bg-primary-600 text-white px-6 py-2 text-sm font-bold rounded-xl shadow-md hover:bg-primary-700 transition hover:-translate-y-0.5">{{ __('messages.save_settings') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
