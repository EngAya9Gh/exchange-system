<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التحقق من الحوالة - {{ $transfer->transfer_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased" style="font-family: 'Cairo', sans-serif;">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <!-- Header Verification Badge -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-8 text-center text-white relative">
                <div class="mx-auto w-16 h-16 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center mb-3">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-xl font-bold">حوالة مالية موثقة</h1>
                <p class="text-emerald-100 text-xs mt-1">تم التحقق من صحة هذه الحوالة من قاعدة البيانات الرسمية</p>
            </div>

            <!-- Transfer Details -->
            <div class="p-6 space-y-6">
                <!-- Status Badge -->
                <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                    <span class="text-sm text-gray-500 font-medium">حالة الحركة:</span>
                    @if($transfer->status === 'paid')
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full border border-emerald-200">تم التسليم للمستفيد</span>
                    @elseif($transfer->status === 'pending')
                        <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full border border-amber-200">قيد الانتظار / جاهزة للصرف</span>
                    @else
                        <span class="px-3 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-full border border-rose-200">ملغاة</span>
                    @endif
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-gray-400 block mb-1">رقم الحوالة:</span>
                        <span class="font-bold text-gray-800 font-mono text-base">{{ $transfer->transfer_number }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block mb-1">تاريخ الإنشاء:</span>
                        <span class="font-semibold text-gray-800">{{ $transfer->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    
                    <div class="col-span-2 pt-2 border-t border-gray-50">
                        <span class="text-xs text-gray-400 block mb-1">المرسل:</span>
                        <span class="font-semibold text-gray-800">{{ $transfer->sender_name }}</span>
                    </div>

                    <div class="col-span-2 pt-2 border-t border-gray-50">
                        <span class="text-xs text-gray-400 block mb-1">المستفيد:</span>
                        <span class="font-semibold text-gray-800">{{ $transfer->recipient_name }}</span>
                    </div>

                    <div class="col-span-2 pt-2 border-t border-gray-50">
                        <span class="text-xs text-gray-400 block mb-1">الفرع المستهدف للاستلام:</span>
                        <span class="font-semibold text-gray-800">{{ $transfer->branch ? $transfer->branch->name : 'غير محدد' }} ({{ $transfer->region->name }})</span>
                    </div>
                </div>

                <!-- Amount Box -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center mt-6">
                    <span class="text-xs text-indigo-500 font-semibold block mb-1">المبلغ الإجمالي المستحق للاستلام</span>
                    <span class="text-2xl font-black text-indigo-700">
                        {{ number_format((float)$transfer->received_amount, 2) }} {{ $transfer->target_currency }}
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 text-center border-t border-gray-100">
                <span class="text-xs text-gray-400">تطبيق نظام الحوالات المالي المشفر &copy; {{ date('Y') }}</span>
            </div>
        </div>
        
        <a href="/" class="text-sm text-gray-500 hover:text-indigo-600 mt-6 underline">العودة للرئيسية</a>
    </div>
</body>
</html>
