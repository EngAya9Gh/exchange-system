<div>
    <div class="mb-4 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ __('messages.bill_payments_system') }}</h2>
        <a href="{{ route('admin.bill-payments-history') }}" class="inline-flex items-center px-4 py-2 bg-primary-100 border border-transparent rounded-md font-semibold text-xs text-primary-700 uppercase tracking-widest hover:bg-primary-200 focus:outline-none focus:border-primary-300 focus:ring focus:ring-primary-200 active:bg-primary-200 transition">
            {{ __('messages.view_bill_history') }}
        </a>
    </div>

    <div class="max-w-3xl mx-auto">
        <!-- نموذج الدفع -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('messages.query_pay_bill') }}</h3>
                
                @if($apiBalance !== null)
                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg px-4 py-2">
                        <span class="text-xs text-blue-600 dark:text-blue-300 font-medium">رصيد مزود الخدمة (API)</span>
                        <div class="text-lg font-bold text-blue-800 dark:text-blue-100">{{ is_numeric($apiBalance) ? number_format($apiBalance, 2) : $apiBalance }}</div>
                    </div>
                @endif
            </div>

            <!-- Tabs -->
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
                    @foreach($categorizedKurumlar as $category => $kurums)
                        <li class="mr-2" role="presentation">
                            <button 
                                wire:click="selectCategory('{{ $category }}')"
                                class="inline-block p-4 border-b-2 rounded-t-lg transition-colors
                                {{ $selectedCategory === $category ? 'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                                type="button" role="tab">
                                {{ $category }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            
            <form wire:submit.prevent="sorgula">
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('messages.select_kurum') }}</h4>
                    @if($selectedCategory && isset($categorizedKurumlar[$selectedCategory]))
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 max-h-60 overflow-y-auto p-1">
                            @foreach($categorizedKurumlar[$selectedCategory] as $kurum)
                                @php
                                    $hasLogo = isset($existingLogos[$kurum['id']]);
                                @endphp
                                <button 
                                    type="button"
                                    wire:click="selectKurum('{{ $kurum['id'] }}')"
                                    class="p-2 rounded-lg text-right transition-all flex flex-row items-center gap-2 min-h-[60px] cursor-pointer border-0 outline-none"
                                    style="
                                        background-color: {{ $selectedKurumId == $kurum['id'] ? '#dc2626' : '#fef2f2' }};
                                        color: {{ $selectedKurumId == $kurum['id'] ? '#ffffff' : '#dc2626' }};
                                        border: none;
                                        box-shadow: {{ $selectedKurumId == $kurum['id'] ? '0 0 0 3px rgba(220,38,38,0.2)' : 'none' }};
                                    "
                                    onmouseover="if({{ $selectedKurumId == $kurum['id'] ? 'false' : 'true' }}) { this.style.backgroundColor='#fee2e2'; }"
                                    onmouseout="if({{ $selectedKurumId == $kurum['id'] ? 'false' : 'true' }}) { this.style.backgroundColor='#fef2f2'; }">
                                    
                                    @if($hasLogo)
                                        <div class="h-10 w-10 shrink-0 flex items-center justify-center bg-white rounded p-0.5">
                                            <img src="{{ asset($existingLogos[$kurum['id']]) }}" alt="Logo" class="max-h-full max-w-full object-contain">
                                        </div>
                                    @endif
                                    <span class="block text-[11px] font-semibold break-words leading-tight flex-1">{{ $kurum['name'] }}</span>
                                </button>
                            @endforeach
                        </div>


                    @else
                        <p class="text-sm text-gray-500">{{ __('messages.no_institutions_category') }}</p>
                    @endif
                    
                    @error('selectedKurumId') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                </div>

                @if($selectedKurumId)
                    <div class="mb-4 animate-fade-in-up">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('messages.subscriber_no') }}</label>
                        <input type="text" wire:model="aboneNo" placeholder="{{ __('messages.enter_subscriber_no') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        @error('aboneNo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent bg-primary-600 py-3 px-4 text-sm font-bold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" 
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="sorgula">{{ __('messages.query_bill') }}</span>
                        <span wire:loading wire:target="sorgula">{{ __('messages.querying') }}</span>
                    </button>
                @endif
            </form>

            @if(session()->has('sorgula_error'))
                <div class="mt-4 text-sm text-red-600">{{ session('sorgula_error') }}</div>
            @endif
            @if(session()->has('sorgula_success'))
                <div class="mt-4 text-sm text-green-600">{{ session('sorgula_success') }}</div>
            @endif

            @if($isQueried)
                <div class="mt-6 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>{{ __('messages.subscriber_name') }}</strong> {{ $queriedName }}</p>
                    </div>

                    @if (session()->has('pay_success'))
                        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                            {{ session('pay_success') }}
                        </div>
                    @endif

                    @if (session()->has('pay_error_admin'))
                        <div class="p-4 mb-4 text-sm rounded-lg border-2 border-red-600 bg-red-100 dark:bg-gray-800" role="alert">
                            <div class="flex items-center gap-2 font-bold text-red-800 dark:text-red-400 mb-1">
                                ⚠️ تنبيه للأدمن - مشكلة في رصيد الـ API
                            </div>
                            <p class="text-red-700 dark:text-red-300">{{ session('pay_error_admin') }}</p>
                            <p class="text-xs text-red-500 mt-1">يجب شحن حساب BayiWebPanel فوراً لاستمرار خدمة دفع الفواتير.</p>
                        </div>
                    @endif

                    @if (session()->has('pay_error'))
                        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                            {{ session('pay_error') }}
                        </div>
                    @endif

                    @if(count($dueBills) > 0)
                        <div class="space-y-4">
                            @foreach($dueBills as $index => $bill)
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md shadow-sm border border-gray-200 dark:border-gray-600 flex flex-col sm:flex-row sm:items-center justify-between">
                                    <div class="mb-2 sm:mb-0">
                                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>{{ __('messages.bill_date') }}</strong> {{ $bill['DueDate'] ?? 'غير محدد' }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>{{ __('messages.bill_number') }}</strong> {{ $bill['BillOrderNumber'] ?? $bill['InvoiceNo'] ?? '' }}</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white"><strong>{{ __('messages.bill_amount') }}</strong> {{ $bill['Amount'] ?? $bill['BillAmount'] ?? 0 }}</p>
                                    </div>
                                    <button wire:click="payBill({{ $index }})" style="background-color: #16a34a; color: white;" class="inline-flex justify-center rounded-md border border-transparent py-1.5 px-3 text-sm font-medium shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="payBill({{ $index }})">{{ __('messages.pay_now') }}</span>
                                        <span wire:loading wire:target="payBill({{ $index }})">{{ __('messages.paying') }}</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
                            {{ __('messages.no_due_bills') }}
                        </div>
                    @endif

                    <button wire:click="resetForm" class="mt-4 w-full text-gray-600 text-sm hover:underline dark:text-gray-400">{{ __('messages.cancel_new_search') }}</button>
                </div>
            @endif
        </div>
    </div>
</div>
