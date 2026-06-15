<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8" dir="rtl">
    <div class="max-w-md mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 mb-4">
                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            </div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">تسليم الحوالة</h2>
            <p class="text-sm text-slate-500 mt-2 font-bold">بوابة التسليم السريع للموظفين</p>
        </div>

        @if($isSuccess)
            <!-- Success / Already Delivered State -->
            <div class="bg-white rounded-3xl shadow-soft p-8 text-center border border-emerald-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-bl-full -z-10"></div>
                <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-2">تم التسليم!</h3>
                @if(session()->has('success_message'))
                    <p class="text-emerald-600 font-bold mb-4">{{ session('success_message') }}</p>
                @else
                    <p class="text-emerald-600 font-bold mb-4">{{ $errorMessage ?: 'هذه الحوالة مسلمة بالفعل.' }}</p>
                @endif
                <div class="text-sm text-slate-500 font-bold border-t border-slate-100 pt-4 mt-2">
                    رقم الحوالة: <span class="text-slate-800 bg-slate-100 px-2 py-1 rounded">{{ $transfer->transfer_number }}</span>
                </div>
                <div class="mt-8">
                    <a href="{{ route('admin.dashboard') }}" class="inline-block w-full py-4 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold transition">العودة للوحة التحكم</a>
                </div>
            </div>
        @else
            <!-- Delivery Form State -->
            <div class="bg-white rounded-3xl shadow-soft border border-slate-100 overflow-hidden">
                <!-- Info Section -->
                <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">رقم الحوالة</p>
                            <p class="font-black text-slate-800">{{ $transfer->transfer_number }}</p>
                        </div>
                        <div class="bg-primary-50 text-primary-600 text-xs font-bold px-3 py-1.5 rounded-full">
                            {{ $transfer->status === 'new' || $transfer->status === 'pending' ? 'جاهزة للتسليم' : $transfer->status }}
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-4">
                        <p class="text-xs font-bold text-slate-400 mb-1">المستفيد</p>
                        <p class="font-bold text-slate-800 text-lg">{{ $transfer->recipient_name }}</p>
                        <p class="text-sm text-slate-500 font-bold mt-1" dir="ltr" style="text-align: right;">{{ $transfer->recipient_phone }}</p>
                    </div>

                    <div class="bg-gradient-to-br from-primary-50 to-rose-50 rounded-2xl p-5 border border-primary-100/50">
                        <p class="text-xs font-bold text-primary-400 mb-1">المبلغ المطلوب تسليمه</p>
                        <div class="flex items-end gap-2">
                            <span class="text-3xl font-black text-slate-800">{{ number_format((float)$transfer->received_amount, 2) }}</span>
                            <span class="text-lg font-bold text-slate-500 mb-1">{{ $transfer->target_currency }}</span>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="p-6">
                    @if($errorMessage)
                        <div class="mb-6 p-4 bg-rose-50 text-rose-600 text-sm font-bold rounded-xl border border-rose-100 flex items-start">
                            <svg class="w-5 h-5 shrink-0 mt-0.5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>{{ $errorMessage }}</p>
                        </div>
                    @endif

                    <form wire:submit="confirmDelivery">
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-2">الرقم السري للإيصال (Secret Code)</label>
                            <input wire:model="secretCode" type="text" class="w-full bg-slate-50 border-none text-slate-800 font-bold text-center text-xl tracking-widest rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-4 transition" placeholder="•• •• ••" required autocomplete="off">
                            <p class="text-xs text-slate-400 font-bold mt-2 text-center">يرجى سؤال الزبون عن الرقم السري الموجود في إيصاله</p>
                        </div>

                        <button type="submit" class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-black text-lg shadow-soft transition-transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            <svg wire:loading.remove wire:target="confirmDelivery" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg wire:loading wire:target="confirmDelivery" class="animate-spin w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>تأكيد التسليم </span>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Warning Note -->
            <p class="text-xs text-center text-slate-400 font-bold mt-6 leading-relaxed">
                بالضغط على زر التأكيد، أنت تقر كموظف في النظام بأنه تم التحقق من هوية المستلم الأصلية وتسليمه المبلغ كاملاً. سيتم تسجيل هذه العملية باسمك.
            </p>
        @endif
    </div>
</div>
