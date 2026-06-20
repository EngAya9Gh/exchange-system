<div class="relative z-10 flex flex-col items-end">
    <span class="text-sm font-bold text-slate-500">الرصيد المتاح</span>
    <span class="text-3xl font-black text-emerald-600">{{ number_format(auth()->user()->balance, 2) }} <span class="text-lg">TRY</span></span>
</div>
