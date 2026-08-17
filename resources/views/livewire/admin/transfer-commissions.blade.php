<div>
    <!-- Header Area -->
    <div class="mb-8">
        <h2 class="text-2xl font-black text-slate-800">عمولات نظام التحويلات</h2>
        <p class="text-slate-500 font-medium mt-1">إدارة نسب وشرائح العمولات الخاصة بنظام الحوالات المالية.</p>
    </div>

    <div class="space-y-6">
        @if (session('commission_success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl font-bold flex items-center">
                <svg class="w-5 h-5 mr-2 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('commission_success') }}
            </div>
        @endif

        <!-- Automated Commissions Toggle -->
        <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">{{ __('messages.automated_commissions_by_tiers') }}</h3>
                <p class="text-sm text-slate-500">{{ __('messages.automated_commissions_desc') }}</p>
            </div>
            <button wire:click="toggleAutomatedCommissions" class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 transition-colors duration-200 ease-in-out {{ $enableAutomatedCommissions ? 'bg-primary-600' : 'bg-slate-200' }}" role="switch" aria-checked="{{ $enableAutomatedCommissions ? 'true' : 'false' }}">
                <span class="sr-only">Toggle automated commissions</span>
                <span aria-hidden="true" class="pointer-events-none absolute h-full w-full rounded-md bg-white opacity-0 transition-opacity duration-200 ease-in-out"></span>
                <span aria-hidden="true" class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $enableAutomatedCommissions ? '-translate-x-3' : 'translate-x-3' }}"></span>
            </button>
        </div>

        <!-- Default Commission Settings -->
        <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">{{ __('messages.default_commission_percentage') }}</h3>
            <p class="text-sm text-slate-500 mb-6">{{ __('messages.default_commission_desc') }}</p>

            <div class="flex items-end gap-4 max-w-sm">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.percentage') }}</label>
                    <input type="number" step="0.01" wire:model="defaultCommission" class="w-full bg-slate-50 text-slate-800 font-bold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3">
                </div>
                <button wire:click="saveDefaultCommission" class="py-3 px-6 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-sm transition">
                    {{ __('messages.save_percentage') }}
                </button>
            </div>
        </div>

        <!-- Role-Based Commission Settings -->
        <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">نسب العمولات حسب الصلاحيات</h3>
            <p class="text-sm text-slate-500 mb-6">حدد النسبة المئوية المخصصة للوكيل وللزبون العادي. (مثال: أدخل 0.5 ليتم احتساب 5 ليرات على كل 1000)</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">نسبة الوكيل (%)</label>
                    <input type="number" step="0.001" wire:model="agentCommission" class="w-full bg-slate-50 text-slate-800 font-bold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3">
                    @error('agentCommission') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">نسبة الزبون العادي (%)</label>
                    <input type="number" step="0.001" wire:model="customerCommission" class="w-full bg-slate-50 text-slate-800 font-bold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3">
                    @error('customerCommission') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <button wire:click="saveRoleCommissions" class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-sm transition">
                        حفظ نسب الصلاحيات
                    </button>
                </div>
            </div>
        </div>

        <!-- Add New Tier -->
        <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">{{ __('messages.add_new_commission_tier') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.from_amount_try') }}</label>
                    <input type="number" wire:model="tierMinAmount" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                    @error('tierMinAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">الفئة المستهدفة</label>
                    <select wire:model="tierTargetRole" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                        <option value="all">الكل</option>
                        <option value="agent">الوكلاء فقط</option>
                        <option value="customer">الزبائن فقط</option>
                    </select>
                    @error('tierTargetRole') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.to_amount_try') }}</label>
                    <input type="number" wire:model="tierMaxAmount" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                    @error('tierMaxAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.type_label') }} {{ __('messages.commission_label') }}</label>
                    <select wire:model="tierCommissionType" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                        <option value="fixed">{{ __('messages.fixed_amount_try') }}</option>
                        <option value="percentage">{{ __('messages.percentage_symbol') }}</option>
                    </select>
                    @error('tierCommissionType') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.value_label') }}</label>
                    <input type="number" step="0.01" wire:model="tierCommissionValue" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                    @error('tierCommissionValue') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button wire:click="saveTier" class="py-3 px-8 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold shadow-sm transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    {{ __('messages.add_tier_btn') }}
                </button>
            </div>
        </div>

        <!-- Tiers List -->
        <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
            <h3 class="text-lg font-bold text-slate-800 mb-6">{{ __('messages.current_tiers') }}</h3>

            <div class="overflow-x-auto rounded-2xl border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">الفئة المستهدفة</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.range_label') }}</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.type_label') }} {{ __('messages.commission_label') }}</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.value_label') }}</th>
                            <th class="px-6 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.actions_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @forelse($tiers as $tier)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($tier->target_role === 'agent')
                                        <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-xs font-bold">الوكلاء</span>
                                    @elseif($tier->target_role === 'customer')
                                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-bold">الزبائن</span>
                                    @else
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold">الكل</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-slate-800">{{ number_format($tier->min_amount, 2) }} - {{ number_format($tier->max_amount, 2) }} TRY</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($tier->commission_type === 'fixed')
                                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold">{{ __('messages.fixed_amount') }}</span>
                                    @else
                                        <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-lg text-xs font-bold">{{ __('messages.percentage_type') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-slate-800">
                                        {{ number_format($tier->commission_value, 2) }}
                                        {{ $tier->commission_type === 'fixed' ? 'TRY' : '%' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button wire:click="deleteTier({{ $tier->id }})" wire:confirm="{{ __('messages.confirm_delete_tier') }}" class="text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 p-2 rounded-lg transition" title="{{ __('messages.delete_btn') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm font-bold">{{ __('messages.no_tiers_added_desc') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
