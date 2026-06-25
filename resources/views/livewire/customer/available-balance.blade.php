<div class="relative z-10 flex flex-col items-end">
    <span class="text-3xl font-black {{ auth()->user()->balance >= 0 ? 'text-emerald-600' : 'text-red-600' }} flex items-center gap-2" dir="ltr">
        <span class="text-2xl" title="تركيا">🇹🇷</span>
        {{ number_format(abs(auth()->user()->balance), 2) }}
        <span class="text-lg">TRY</span>
    </span>
</div>
