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

    <!-- Reopen Last Summary Banner -->
    @if(!empty($lastSummaryData) && !$showSummaryModal)
        <div class="mb-5 p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-emerald-50 via-teal-50/50 to-white border border-emerald-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 animate-in fade-in duration-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-500/20 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-bold text-emerald-800">آخر عملية تم تنفيذها (سند #{{ $lastSummaryData['transaction_id'] }})</div>
                    <div class="text-sm font-black text-gray-900">
                        {{ $lastSummaryData['user_name'] }} • 
                        <span class="font-mono text-emerald-700" dir="ltr">{{ $lastSummaryData['type'] === 'deposit' ? '+' : '-' }} {{ number_format($lastSummaryData['amount'], 2) }} ₺</span>
                        <span class="text-xs font-normal text-gray-500">({{ $lastSummaryData['type_text'] }})</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button wire:click="reopenLastSummary" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition hover:scale-105 active:scale-95">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    إعادة فتح التفاصيل للمشاركة
                </button>
                <button wire:click="dismissLastSummaryBanner" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-white/80 transition" title="إخفاء">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
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
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $user->name }}</div>
                            @if($user->username)
                                <div class="text-[10px] text-gray-400 font-mono mt-1">{{ $user->username }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold font-mono text-gray-500" dir="ltr">{{ $user->phone }}</td>
                        <td class="px-6 py-4 font-bold text-center">
                            <div class="flex items-center justify-center gap-2">
                                <span class="{{ $user->balance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ number_format(abs($user->balance), 2) }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $user->balance >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $user->balance >= 0 ? 'دائن (له)' : 'مدين (عليه)' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->has_unlimited_balance)
                                <span class="text-gray-500 text-xs font-bold">{{ __('messages.unlimited_limit') }}</span>
                            @else
                                <span class="text-red-600 font-bold">{{ number_format($user->balance_limit, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button wire:click="openStatementModal({{ $user->id }})" class="text-white font-bold text-xs bg-slate-500 hover:bg-slate-600 px-3 py-1.5 rounded-lg shadow-sm transition">
                                كشف الحساب
                            </button>
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
                            <p class="text-sm text-gray-500 mb-6">{{ __('messages.customer') }}: <span class="font-bold text-gray-900">{{ $manageUserName }}</span> | {{ __('messages.current_balance') }}: 
                                <span class="font-bold {{ $manageUserBalance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ number_format(abs($manageUserBalance), 2) }}
                                    <span class="text-xs {{ $manageUserBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $manageUserBalance >= 0 ? '(دائن)' : '(مدين)' }}
                                    </span>
                                </span>
                            </p>
                            
                            <div class="mb-4 text-start">
                                <label class="block text-sm font-bold text-gray-700 mb-2">المبلغ (اكتب الرقم بدون أي إشارات)</label>
                                <input type="number" wire:model="depositAmount" min="0" step="0.01" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 font-bold text-lg" placeholder="500">
                                @error('depositAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row justify-between gap-3 border-t border-gray-100">
                        <button wire:click="closeModals" class="bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-xl shadow-sm transition w-full sm:w-auto">إلغاء</button>
                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <button wire:click="confirmWithdrawal" class="bg-rose-500 text-white px-6 py-2 text-sm font-bold rounded-xl shadow-md hover:bg-rose-600 transition hover:-translate-y-0.5 w-full sm:w-auto">سحب (خصم -)</button>
                            <button wire:click="confirmDeposit" class="bg-emerald-500 text-white px-6 py-2 text-sm font-bold rounded-xl shadow-md hover:bg-emerald-600 transition hover:-translate-y-0.5 w-full sm:w-auto">إيداع (إضافة +)</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Operation Summary Pop-up Modal -->
    @if($showSummaryModal && !empty($summaryData))
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="summary-modal-title" role="dialog" aria-modal="true"
             x-data="{ copied: false }">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-6 text-center sm:p-0">
                <!-- Backdrop with blur -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" wire:click="closeSummaryModal"></div>

                <div class="inline-block align-middle bg-white rounded-[28px] text-start overflow-hidden shadow-2xl border border-gray-100 transform transition-all sm:my-8 sm:max-w-md w-full relative z-10">
                    
                    <!-- Top color accent bar -->
                    <div class="h-2.5 w-full {{ $summaryData['type'] === 'deposit' ? 'bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-500' : 'bg-gradient-to-r from-rose-400 via-rose-500 to-red-500' }}"></div>

                    <!-- Modal Body -->
                    <div class="p-6 sm:p-7">
                        <!-- Company Logo & Header -->
                        <div class="text-center mb-5">
                            <div class="inline-flex items-center justify-center mb-3.5 p-2 bg-gray-50/90 rounded-2xl border border-gray-100 shadow-sm">
                                <img src="{{ asset('logo.png?v=2') }}" alt="Company Logo" class="h-12 max-w-[170px] object-contain drop-shadow-sm">
                            </div>

                            <div class="flex items-center justify-center gap-2 mb-2">
                                @if($summaryData['type'] === 'deposit')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        {{ $summaryData['type_text'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-200">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" />
                                        </svg>
                                        {{ $summaryData['type_text'] }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                    #{{ $summaryData['transaction_id'] }}
                                </span>
                            </div>

                            <h3 class="text-xl font-black text-gray-900 tracking-tight" id="summary-modal-title">
                                ملخص العملية الناجحة
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">تم تسجيل العملية وتحديث رصيد الحساب بنجاح</p>
                        </div>

                        <!-- Amount Display (Hero Card) -->
                        <div class="rounded-2xl p-4 mb-5 text-center relative overflow-hidden {{ $summaryData['type'] === 'deposit' ? 'bg-gradient-to-br from-emerald-50 via-teal-50/40 to-white border border-emerald-200/80 shadow-sm' : 'bg-gradient-to-br from-rose-50 via-red-50/40 to-white border border-rose-200/80 shadow-sm' }}">
                            <div class="text-xs font-bold text-gray-500 mb-1">المبلغ المحدد</div>
                            <div class="text-3xl sm:text-4xl font-black font-mono tracking-tight {{ $summaryData['type'] === 'deposit' ? 'text-emerald-600' : 'text-rose-600' }}" dir="ltr">
                                {{ $summaryData['type'] === 'deposit' ? '+' : '-' }} {{ number_format($summaryData['amount'], 2) }}
                                <span class="text-base font-bold text-gray-700">ليرة تركية</span>
                            </div>
                        </div>

                        <!-- Details Rows -->
                        <div class="bg-gray-50/90 rounded-2xl p-4 border border-gray-100 divide-y divide-gray-100 space-y-2.5 text-sm">
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-gray-500 font-bold">اسم الشخص / الزبون:</span>
                                <span class="font-black text-gray-900 text-base">{{ $summaryData['user_name'] }}</span>
                            </div>

                            <div class="flex items-center justify-between pt-2.5">
                                <span class="text-gray-500 font-medium">الرصيد السابق:</span>
                                <span class="font-bold font-mono text-gray-700" dir="ltr">
                                    {{ number_format(abs($summaryData['balance_before']), 2) }} ₺
                                    <span class="text-xs font-normal text-gray-500">
                                        ({{ $summaryData['balance_before'] >= 0 ? 'له' : 'عليه' }})
                                    </span>
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-2.5">
                                <span class="text-gray-500 font-medium">الرصيد بعد العملية:</span>
                                <span class="font-black font-mono {{ $summaryData['balance_after'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}" dir="ltr">
                                    {{ number_format(abs($summaryData['balance_after']), 2) }} ₺
                                    <span class="text-xs font-bold">
                                        ({{ $summaryData['balance_after'] >= 0 ? 'دائن / له' : 'مدين / عليه' }})
                                    </span>
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-2.5">
                                <span class="text-gray-500 font-medium">تاريخ ووقت التنفيذ:</span>
                                <span class="font-medium text-xs font-mono text-gray-600" dir="ltr">{{ $summaryData['date'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row gap-2.5">
                        <button type="button"
                                @click="
                                    const text = `إشعار عملية مالية\n----------------\nالزبون: {{ $summaryData['user_name'] }}\nنوع العملية: {{ $summaryData['type_text'] }}\nالمبلغ: {{ number_format($summaryData['amount'], 2) }} ليرة تركية\nالرصيد بعد العملية: {{ number_format(abs($summaryData['balance_after']), 2) }} ليرة تركية ({{ $summaryData['balance_after'] >= 0 ? 'دائن' : 'مدين' }})\nالتاريخ: {{ $summaryData['date'] }}\nرقم السند: #{{ $summaryData['transaction_id'] }}`;
                                    navigator.clipboard.writeText(text);
                                    copied = true;
                                    setTimeout(() => copied = false, 2500);
                                "
                                class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs sm:text-sm font-bold shadow-sm transition hover:border-gray-400">
                            <svg x-show="!copied" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-text="copied ? 'تم النسخ!' : 'نسخ الإشعار'"></span>
                        </button>

                        <button type="button"
                                @click="
                                    const shareText = encodeURIComponent(`إشعار عملية مالية\n----------------\nالزبون: {{ $summaryData['user_name'] }}\nنوع العملية: {{ $summaryData['type_text'] }}\nالمبلغ: {{ number_format($summaryData['amount'], 2) }} ليرة تركية\nالرصيد بعد العملية: {{ number_format(abs($summaryData['balance_after']), 2) }} ليرة تركية ({{ $summaryData['balance_after'] >= 0 ? 'دائن' : 'مدين' }})\nالتاريخ: {{ $summaryData['date'] }}\nرقم السند: #{{ $summaryData['transaction_id'] }}`);
                                    let phone = '{{ $summaryData['user_phone'] ?? '' }}'.replace(/[^0-9]/g, '');
                                    let url = phone ? `https://wa.me/${phone}?text=${shareText}` : `https://wa.me/?text=${shareText}`;
                                    window.open(url, '_blank');
                                "
                                class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-emerald-500 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs sm:text-sm font-bold shadow-sm transition hover:scale-105 active:scale-95">
                            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                            </svg>
                            واتساب
                        </button>

                        <button wire:click="closeSummaryModal"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm text-white shadow-md transition transform active:scale-95 {{ $summaryData['type'] === 'deposit' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/25' : 'bg-rose-600 hover:bg-rose-700 shadow-rose-500/25' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $openedFromStatementUserId ? 'العودة لكشف الحساب' : 'حسناً / إغلاق' }}
                        </button>
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
                            <p class="text-sm text-gray-500 mb-6">{{ __('messages.customer') }}: <span class="font-bold text-gray-900">{{ $manageUserName }}</span> | {{ __('messages.current_balance') }}: 
                                <span class="font-bold {{ $manageUserBalance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ number_format(abs($manageUserBalance), 2) }}
                                    <span class="text-xs {{ $manageUserBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $manageUserBalance >= 0 ? '(دائن)' : '(مدين)' }}
                                    </span>
                                </span>
                            </p>
                            
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

    <!-- Statement Modal -->
    @if($showStatementModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity" wire:click="closeModals"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[24px] text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-3 text-center sm:mt-0 sm:text-start w-full">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-xl leading-6 font-black text-gray-900 mb-2" id="modal-title">كشف حساب</h3>
                                    <p class="text-sm text-gray-500">{{ __('messages.customer') }}: <span class="font-bold text-gray-900">{{ $manageUserName }}</span> | {{ __('messages.current_balance') }}: 
                                        <span class="font-bold {{ $manageUserBalance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ number_format(abs($manageUserBalance), 2) }}
                                            <span class="text-xs {{ $manageUserBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                {{ $manageUserBalance >= 0 ? '(دائن)' : '(مدين)' }}
                                            </span>
                                        </span>
                                    </p>
                                </div>
                                <button wire:click="closeModals" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto max-h-[60vh] border border-gray-100 rounded-xl">
                                <table class="w-full text-sm text-center text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 shadow-sm">
                                        <tr>
                                            <th class="px-4 py-3">التاريخ والوقت</th>
                                            <th class="px-4 py-3">نوع العملية</th>
                                            <th class="px-4 py-3">المبلغ</th>
                                            <th class="px-4 py-3">الرصيد بعد العملية</th>
                                            <th class="px-4 py-3">البيان (ملاحظات)</th>
                                            <th class="px-4 py-3">بواسطة</th>
                                            <th class="px-4 py-3 text-center">التفاصيل</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($statementTransactions as $tx)
                                            <tr class="hover:bg-gray-50/50 transition">
                                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-gray-600" dir="ltr">{{ $tx->created_at->format('Y-m-d h:i A') }}</td>
                                                <td class="px-4 py-3 font-bold">
                                                    @if($tx->type === 'deposit')
                                                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-1 rounded">إيداع</span>
                                                    @elseif($tx->type === 'withdrawal')
                                                        <span class="bg-rose-100 text-rose-800 text-xs px-2 py-1 rounded">سحب</span>
                                                    @elseif($tx->type === 'payment')
                                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">سداد دفعة</span>
                                                    @else
                                                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">تعديل رصيد</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 font-bold whitespace-nowrap" dir="ltr">
                                                    @if(in_array($tx->type, ['deposit', 'payment']) || ($tx->type === 'adjustment' && $tx->balance_after >= $tx->balance_before))
                                                        <span class="text-emerald-600">+ {{ number_format($tx->amount, 2) }}</span>
                                                    @else
                                                        <span class="text-rose-600">- {{ number_format($tx->amount, 2) }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 font-bold whitespace-nowrap" dir="ltr">
                                                    <span class="{{ $tx->balance_after >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        {{ number_format(abs($tx->balance_after), 2) }}
                                                        <span class="text-[10px] font-normal">
                                                             {{ $tx->balance_after >= 0 ? '(دائن)' : '(مدين)' }}
                                                        </span>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate" title="{{ $tx->notes }}">
                                                    {{ $tx->notes ?? '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-xs text-gray-400">
                                                    {{ $tx->admin?->name ?? 'نظام' }}
                                                </td>
                                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                                    <button wire:click="openTransactionSummary({{ $tx->id }})" title="عرض التفاصيل" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition hover:scale-105 active:scale-95 shadow-xs">
                                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        تفاصيل
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد حركات مالية مسجلة لهذا المستخدم.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
