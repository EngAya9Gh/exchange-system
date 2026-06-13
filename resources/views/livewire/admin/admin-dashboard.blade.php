<div class="py-6 font-sans text-right" dir="rtl" x-data="{ rejectModal: false, rejectId: null, rejectNotes: '' }">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-2 flex space-x-2 space-x-reverse overflow-x-auto">
            <button wire:click="$set('activeTab', 'dashboard')" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all {{ $activeTab === 'dashboard' ? 'bg-red-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' }}">
                لوحة التحكم العامة
            </button>
            <button wire:click="$set('activeTab', 'new_transfer')" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all {{ $activeTab === 'new_transfer' ? 'bg-red-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' }}">
                تسجيل حوالة جديدة
            </button>
            <button wire:click="$set('activeTab', 'ledger')" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all {{ $activeTab === 'ledger' ? 'bg-red-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' }}">
                دفتر الحوالات
            </button>
            <button wire:click="$set('activeTab', 'rates')" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all {{ $activeTab === 'rates' ? 'bg-red-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' }}">
                أسعار الصرف
            </button>
        </div>

        <!-- TAB 1: General Dashboard -->
        @if ($activeTab === 'dashboard')
            <!-- Balance Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- TRY Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase">رصيد الليرة التركية الصادر</span>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalTrySent, 2) }} <span class="text-sm font-bold text-gray-400">TRY</span></h3>
                    </div>
                    <div class="text-xs text-emerald-600 font-semibold mt-4">إجمالي الصفقات المنفذة</div>
                </div>
                <!-- USD Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase">رصيد الدولار الأمريكي الصادر</span>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalUsdSent, 2) }} <span class="text-sm font-bold text-gray-400">USD</span></h3>
                    </div>
                    <div class="text-xs text-emerald-600 font-semibold mt-4">إجمالي الصفقات المنفذة</div>
                </div>
                <!-- EUR Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase">رصيد اليورو الصادر</span>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalEurSent, 2) }} <span class="text-sm font-bold text-gray-400">EUR</span></h3>
                    </div>
                    <div class="text-xs text-emerald-600 font-semibold mt-4">إجمالي الصفقات المنفذة</div>
                </div>
                <!-- EGP Card (مقوم) -->
                <div class="bg-gradient-to-br from-red-600 to-red-700 text-white rounded-2xl shadow-md p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-xs text-red-200 font-bold uppercase">رصيد فودافون كاش المقوم الموزع</span>
                        <h3 class="text-2xl font-black mt-1">{{ number_format($totalEgpPaid, 2) }} <span class="text-sm font-bold text-red-200">EGP</span></h3>
                    </div>
                    <div class="text-xs text-red-100 font-semibold mt-4">إجمالي المبالغ المسلمة فعلياً</div>
                </div>
            </div>

            <!-- Incoming Customer Requests Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 border-b border-gray-50 pb-3 mb-4">طلبات التحويل المعلقة الواردة من الزبائن</h3>
                
                @if (session()->has('request_success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 text-sm rounded-lg border border-emerald-100">
                        {{ session('request_success') }}
                    </div>
                @endif
                @if (session()->has('request_error'))
                    <div class="mb-4 p-3 bg-rose-50 text-rose-800 text-sm rounded-lg border border-rose-100">
                        {{ session('request_error') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">الزبون المرسل</th>
                                <th class="px-6 py-3">المستفيد</th>
                                <th class="px-6 py-3">المبلغ المطلق</th>
                                <th class="px-6 py-3">تاريخ التقديم</th>
                                <th class="px-6 py-3 text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomingRequests as $req)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ $req->user->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $req->sender_phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ $req->recipient_name }}</div>
                                        <div class="text-xs text-gray-400">{{ $req->recipient_phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-800">
                                        {{ number_format((float)$req->amount, 2) }} {{ $req->currency }}
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        {{ $req->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2 space-x-reverse">
                                        <button wire:click="approveRequest({{ $req->id }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm">
                                            قبول الحوالة
                                        </button>
                                        <button x-on:click="rejectId = {{ $req->id }}; rejectModal = true" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-sm">
                                            رفض
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                        لا توجد طلبات معلقة حالياً.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- TAB 2: New Manual Transfer -->
        @if ($activeTab === 'new_transfer')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 border-b border-gray-50 pb-3 mb-6">تسجيل حوالة جديدة يدوياً</h3>
                
                @if (session()->has('transfer_success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 text-sm rounded-lg border border-emerald-100">
                        {{ session('transfer_success') }}
                    </div>
                @endif

                <form wire:submit="submitManualTransfer" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Sender -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg">المرسل</h4>
                            <div>
                                <x-input-label for="sender_name" value="اسم المرسل الكامل" />
                                <x-text-input wire:model="sender_name" id="sender_name" type="text" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="sender_phone" value="رقم هاتف المرسل (واتساب)" />
                                <x-text-input wire:model="sender_phone" id="sender_phone" type="text" class="mt-1 block w-full" required />
                            </div>
                        </div>

                        <!-- Recipient -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg">المستفيد</h4>
                            <div>
                                <x-input-label for="recipient_name" value="اسم المستفيد الكامل" />
                                <x-text-input wire:model="recipient_name" id="recipient_name" type="text" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="recipient_phone" value="رقم هاتف المستفيد" />
                                <x-text-input wire:model="recipient_phone" id="recipient_phone" type="text" class="mt-1 block w-full" required />
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Setup -->
                        <div class="space-y-4 md:col-span-2">
                            <h4 class="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-1.5 rounded-lg">المنطقة والفرع وقيمة الحوالة</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label value="المنطقة" />
                                    <select wire:model.live="region_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2">
                                        @foreach($regions as $region)
                                            <option value="{{ $region['id'] }}">{{ $region['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="فرع الاستلام" />
                                    <select wire:model="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2">
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2">
                                    <x-input-label value="المبلغ المرسل" />
                                    <x-text-input wire:model.live="amount" type="number" step="0.01" class="mt-1 block w-full font-bold text-lg" required />
                                </div>
                                <div>
                                    <x-input-label value="العملة" />
                                    <select wire:model.live="source_currency" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2 font-bold">
                                        <option value="TRY">TRY</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Computations -->
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 flex flex-col justify-between space-y-4">
                            <div class="space-y-3 text-sm">
                                <h4 class="text-xs font-bold text-slate-500 uppercase">تفاصيل الحسبة الحالية</h4>
                                <div class="flex justify-between">
                                    <span>سعر الصرف:</span>
                                    <span class="font-bold">{{ number_format($exchange_rate, 4) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>العمولة المقتطعة:</span>
                                    <span class="font-bold">{{ number_format($commission, 2) }} {{ $source_currency }}</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t">
                                    <span>الصافي للمستفيد:</span>
                                    <span class="font-black text-emerald-600 text-lg">{{ number_format($received_amount, 2) }} EGP</span>
                                </div>
                            </div>
                            <div class="pt-4 border-t">
                                <div class="flex justify-between text-base font-bold text-slate-800 mb-3">
                                    <span>إجمالي المقبوض:</span>
                                    <span>{{ number_format($total_to_pay, 2) }} {{ $source_currency }}</span>
                                </div>
                                <button type="submit" class="w-full justify-center bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-xl font-bold transition shadow-sm">
                                    تسجيل وطباعة الإيصال
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        <!-- TAB 3: Transfer Ledger (دفتر الحوالات) -->
        @if ($activeTab === 'ledger')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-center pb-4 border-b border-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">دفتر الحوالات العام</h3>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mt-4 sm:mt-0 w-full sm:w-auto">
                        <x-text-input wire:model.live="searchQuery" placeholder="بحث برقم الحوالة، المرسل، المستفيد..." class="text-sm py-1.5 w-full sm:w-64" />
                        <select wire:model.live="ledgerStatusFilter" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-1.5">
                            <option value="all">كل الحالات</option>
                            <option value="pending">قيد الانتظار</option>
                            <option value="paid">تم التسليم</option>
                            <option value="cancelled">ملغاة</option>
                        </select>
                    </div>
                </div>

                @if (session()->has('ledger_success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 text-sm rounded-lg border border-emerald-100">
                        {{ session('ledger_success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">رقم الحوالة / الرمز</th>
                                <th class="px-6 py-3">المرسل والمستفيد</th>
                                <th class="px-6 py-3">المبالغ</th>
                                <th class="px-6 py-3">المنطقة والفرع</th>
                                <th class="px-6 py-3">تاريخ الحركة</th>
                                <th class="px-6 py-3">الحالة</th>
                                <th class="px-6 py-3 text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $tr)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 font-mono">{{ $tr->transfer_number }}</div>
                                        <div class="text-xs text-emerald-600 font-bold mt-1 bg-emerald-50 px-2 py-0.5 rounded inline-block">الرمز: {{ $tr->secret_code }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ $tr->recipient_name }}</div>
                                        <div class="text-xs text-gray-400">من: {{ $tr->sender_name }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800">{{ number_format((float)$tr->source_amount, 2) }} {{ $tr->source_currency }}</div>
                                        <div class="text-xs text-indigo-600 font-medium">الصافي: {{ number_format((float)$tr->received_amount, 2) }} {{ $tr->target_currency }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <div>{{ $tr->region->name }}</div>
                                        <div class="text-gray-400 mt-1">{{ $tr->branch ? $tr->branch->name : 'غير محدد' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        {{ $tr->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($tr->status === 'paid')
                                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full">تم الصرف</span>
                                        @elseif($tr->status === 'cancelled')
                                            <span class="px-2.5 py-1 bg-rose-100 text-rose-800 text-xs font-semibold rounded-full">ملغاة</span>
                                        @else
                                            <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full">جاهزة للصرف</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2 space-x-reverse">
                                        @if($tr->status === 'pending')
                                            <button wire:click="payTransfer({{ $tr->id }})" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold">
                                                صرف الآن
                                            </button>
                                        @endif
                                        <button wire:click="viewReceipt({{ $tr->id }})" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold">
                                            الإيصال
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                        لا توجد حوالات مطابقة لمعايير البحث.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $transfers->links() }}
                </div>
            </div>
        @endif

        <!-- TAB 4: Exchange Rates (أسعار الصرف) -->
        @if ($activeTab === 'rates')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 border-b border-gray-50 pb-3 mb-6">إدارة أسعار الصرف</h3>
                
                @if (session()->has('rate_success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 text-sm rounded-lg border border-emerald-100">
                        {{ session('rate_success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">المنطقة</th>
                                <th class="px-6 py-3">العملة المصدر</th>
                                <th class="px-6 py-3">العملة الهدف</th>
                                <th class="px-6 py-3">سعر الصرف الحالي</th>
                                <th class="px-6 py-3 text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exchangeRates as $rate)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        {{ $rate->region ? $rate->region->name : 'افتراضي عام لكافة الفروع' }}
                                    </td>
                                    <td class="px-6 py-4 font-bold">{{ $rate->from_currency }}</td>
                                    <td class="px-6 py-4 font-bold">{{ $rate->to_currency }}</td>
                                    <td class="px-6 py-4">
                                        <input type="number" step="0.00001" wire:model="adjustedRates.{{ $rate->id }}" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-bold w-32 py-1">
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button wire:click="updateRate({{ $rate->id }})" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold">
                                            حفظ السعر الجديد
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    <!-- REJECT REQUEST MODAL -->
    <div x-show="rejectModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-gray-800">سبب رفض طلب التحويل</h3>
            <textarea x-model="rejectNotes" class="w-full border-gray-300 rounded-lg text-sm" placeholder="اكتب سبب الرفض هنا..." rows="3"></textarea>
            <div class="flex justify-end space-x-2 space-x-reverse">
                <button x-on:click="rejectModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold">إلغاء</button>
                <button x-on:click="
                    $wire.rejectRequest(rejectId, rejectNotes);
                    rejectModal = false;
                    rejectNotes = '';
                " class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold">تأكيد الرفض</button>
            </div>
        </div>
    </div>

    <!-- RECEIPT VIEWER MODAL -->
    @if($showReceiptModal && $selectedTransfer)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="bg-slate-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800">إيصال الاستلام للحوالة الموثقة</h3>
                    <button wire:click="$set('showReceiptModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- PDF Preview -->
                <div class="flex-1 overflow-y-auto p-6 bg-slate-100">
                    <iframe src="{{ $receiptPdfUrl }}" class="w-full h-[380px] border border-gray-200 rounded-lg bg-white shadow-sm" frameborder="0"></iframe>
                </div>

                <!-- Action Footer Buttons -->
                <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 grid grid-cols-3 gap-2">
                    <button onclick="navigator.clipboard.writeText('{{ $selectedTransfer->transfer_number }}')" class="px-3 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold flex justify-center items-center shadow-xs">
                        نسخ رقم الحوالة
                    </button>
                    <a href="{{ $receiptPdfUrl }}" download class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold flex justify-center items-center shadow-sm text-center">
                        تحميل الـ PDF
                    </a>
                    <button onclick="window.open('{{ $receiptPdfUrl }}', '_blank')" class="px-3 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold flex justify-center items-center shadow-xs">
                        طباعة الإيصال
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
