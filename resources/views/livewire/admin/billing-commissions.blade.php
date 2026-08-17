<div>
    <!-- Header Area -->
    <div class="mb-8">
        <h2 class="text-2xl font-black text-slate-800">عمولات نظام الفواتير</h2>
        <p class="text-slate-500 font-medium mt-1">إدارة نسب العمولات الخاصة بتسديد الفواتير والخدمات.</p>
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
                <h3 class="text-lg font-bold text-slate-800 mb-1">تفعيل عمولات الفواتير الآلية</h3>
                <p class="text-sm text-slate-500">عند التفعيل، سيتم حساب عمولات تسديد الفواتير تلقائياً بناءً على النسب المحددة أدناه.</p>
            </div>
            <button wire:click="toggleAutomatedCommissions" class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 transition-colors duration-200 ease-in-out {{ $enableAutomatedCommissions ? 'bg-primary-600' : 'bg-slate-200' }}" role="switch" aria-checked="{{ $enableAutomatedCommissions ? 'true' : 'false' }}">
                <span class="sr-only">Toggle automated commissions</span>
                <span aria-hidden="true" class="pointer-events-none absolute h-full w-full rounded-md bg-white opacity-0 transition-opacity duration-200 ease-in-out"></span>
                <span aria-hidden="true" class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $enableAutomatedCommissions ? '-translate-x-3' : 'translate-x-3' }}"></span>
            </button>
        </div>

        <!-- Default Commission Settings -->
        <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">نسبة العمولة الافتراضية للفواتير</h3>
            <p class="text-sm text-slate-500 mb-6">هذه هي النسبة التي سيتم اقتطاعها كعمولة في حال لم تكن هناك نسبة خاصة بالصلاحية.</p>

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
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">نسب عمولات الفواتير حسب الصلاحيات</h3>
            <p class="text-sm text-slate-500 mb-6">حدد النسبة المئوية المخصصة للوكيل وللزبون العادي لتسديد الفواتير.</p>

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

    </div>
</div>
