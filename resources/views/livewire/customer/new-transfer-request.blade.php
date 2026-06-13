<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-800 border-b border-gray-50 pb-3 mb-6">طلب تحويل مالي جديد</h3>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-800 text-sm rounded-xl border border-emerald-100 flex items-center">
            <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="submitRequest" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sender Info -->
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg">بيانات المرسل</h4>
                
                <div>
                    <x-input-label for="sender_name" value="اسم المرسل الكامل" class="text-right" />
                    <x-text-input wire:model="sender_name" id="sender_name" type="text" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('sender_name')" class="mt-1 text-right" />
                </div>

                <div>
                    <x-input-label for="sender_phone" value="رقم هاتف المرسل (واتساب)" class="text-right" />
                    <x-text-input wire:model="sender_phone" id="sender_phone" type="text" class="mt-1 block w-full" placeholder="+90..." required />
                    <x-input-error :messages="$errors->get('sender_phone')" class="mt-1 text-right" />
                </div>
            </div>

            <!-- Recipient Info -->
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg">بيانات المستفيد</h4>
                
                <div>
                    <x-input-label for="recipient_name" value="اسم المستفيد الكامل" class="text-right" />
                    <x-text-input wire:model="recipient_name" id="recipient_name" type="text" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('recipient_name')" class="mt-1 text-right" />
                </div>

                <div>
                    <x-input-label for="recipient_phone" value="رقم هاتف المستفيد" class="text-right" />
                    <x-text-input wire:model="recipient_phone" id="recipient_phone" type="text" class="mt-1 block w-full" placeholder="+20..." required />
                    <x-input-error :messages="$errors->get('recipient_phone')" class="mt-1 text-right" />
                </div>
            </div>
        </div>

        <hr class="border-gray-100">

        <!-- Financial Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Region & Branch -->
            <div class="space-y-4 md:col-span-2">
                <h4 class="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg">المنطقة والفرع المستهدف</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="region_id" value="المنطقة" class="text-right" />
                        <select wire:model.live="region_id" id="region_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2">
                            @foreach($regions as $region)
                                <option value="{{ $region['id'] }}">{{ $region['name'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('region_id')" class="mt-1 text-right" />
                    </div>

                    <div>
                        <x-input-label for="branch_id" value="فرع الاستلام" class="text-right" />
                        <select wire:model="branch_id" id="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2">
                            @if(count($branches) === 0)
                                <option value="">لا توجد فروع متاحة</option>
                            @endif
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('branch_id')" class="mt-1 text-right" />
                    </div>
                </div>

                <!-- Amount Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="amount" value="مبلغ التحويل" class="text-right" />
                        <x-text-input wire:model.live="amount" id="amount" type="number" step="0.01" class="mt-1 block w-full font-bold text-lg" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-1 text-right" />
                    </div>

                    <div>
                        <x-input-label for="currency" value="العملة" class="text-right" />
                        <select wire:model.live="currency" id="currency" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2 font-bold">
                            <option value="TRY">TRY (ليرة)</option>
                            <option value="USD">USD (دولار)</option>
                            <option value="EUR">EUR (يورو)</option>
                        </select>
                        <x-input-error :messages="$errors->get('currency')" class="mt-1 text-right" />
                    </div>
                </div>
            </div>

            <!-- Summary Box -->
            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">تفاصيل الحسبة التقديرية</h4>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">سعر الصرف (إلى EGP):</span>
                        <span class="font-bold text-gray-800">{{ number_format($exchange_rate, 4) }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">العمولة المقتطعة:</span>
                        <span class="font-bold text-gray-800">{{ number_format($commission, 2) }} {{ $currency }}</span>
                    </div>

                    <div class="flex justify-between text-sm pt-2 border-t border-slate-200">
                        <span class="text-gray-500 font-medium">الصافي للمستفيد:</span>
                        <span class="font-black text-emerald-600 text-lg">{{ number_format($received_amount, 2) }} EGP</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <div class="flex justify-between text-base font-bold text-slate-800 mb-3">
                        <span>إجمالي المطلوب دفعه:</span>
                        <span>{{ number_format($total_to_pay, 2) }} {{ $currency }}</span>
                    </div>

                    <x-primary-button class="w-full justify-center bg-red-600 hover:bg-red-700 active:bg-red-800 focus:ring-red-500 py-2.5">
                        إرسال الطلب الآن
                    </x-primary-button>
                </div>
            </div>
        </div>
    </form>
</div>
