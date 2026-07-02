<div class="min-h-screen bg-slate-900 font-sans text-right" dir="rtl" style="background-image: radial-gradient(circle at top right, #1e293b 0%, #0f172a 100%);">
    
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-red-600/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-1/3 -left-20 w-72 h-72 bg-rose-500/10 rounded-full blur-3xl" style="animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;"></div>
    </div>

    <!-- Navigation / Header -->
    <nav class="relative z-10 p-6 flex justify-between items-center max-w-6xl mx-auto">
        <div class="flex items-center gap-3">
            <img src="{{ asset('logo.png?v=2') }}" alt="Teacher VC" class="h-20 lg:h-24 object-contain drop-shadow-xl brightness-200">
        </div>
        <div>
            <a href="{{ route('login') }}" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-bold rounded-xl backdrop-blur-md transition-all shadow-lg hover:shadow-red-500/20">
                تسجيل الدخول
            </a>
        </div>
    </nav>

    <!-- Main Hero Content -->
    <div class="relative z-10 max-w-6xl mx-auto px-6 py-12 lg:py-20 flex flex-col lg:flex-row items-center justify-between gap-12">
        
        <!-- Text Area -->
        <div class="w-full lg:w-1/2 text-center lg:text-right">
            <span class="inline-block py-1.5 px-4 rounded-full bg-red-500/20 text-red-400 text-xs font-bold border border-red-500/30 mb-8">
                🚀 أسعار محدّثة لحظياً
            </span>
            <h1 class="text-4xl lg:text-6xl font-black text-white leading-relaxed mb-6 drop-shadow-md">
                <span class="block mb-2">سعر صرف</span>
                <span class="block pb-4 pt-2 -mb-2 text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-rose-400">الجنيه المصري</span>
                <span class="block">الآن بين يديك!</span>
            </h1>
            <p class="text-lg text-slate-300 mb-10 max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                في Teacher VC نضمن لك تحويلاً سريعاً وآمناً من الليرة التركية إلى الجنيه المصري بأفضل أسعار السوق. جرب الحاسبة الآن.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                <a href="https://wa.me/905392065497?text={{ urlencode('مرحباً، أود الاستفسار بخصوص تحويل الأموال..') }}" target="_blank" class="w-full sm:w-auto px-8 py-4 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-2xl shadow-[0_0_30px_rgba(16,185,129,0.4)] transition-all hover:-translate-y-1 flex items-center justify-center gap-3">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    تواصل عبر واتساب
                </a>
                <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-bold rounded-2xl transition-all border border-white/10 text-center">
                    حساب جديد
                </a>
            </div>
            
            <!-- Trust Indicators -->
            <div class="mt-10 flex items-center justify-center lg:justify-start gap-6 opacity-70">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span class="text-sm font-bold text-white">آمن وموثوق</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span class="text-sm font-bold text-white">تحويل فوري</span>
                </div>
            </div>
        </div>

        <!-- Calculator Glass Card -->
        <div class="w-full lg:w-1/2 max-w-md relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-red-600 to-rose-600 rounded-[3rem] blur opacity-30 animate-pulse"></div>
            <div class="relative bg-slate-800/60 backdrop-blur-2xl border border-slate-700/50 p-8 rounded-[2.5rem] shadow-2xl">
                
                <div class="text-center mb-8">
                    <p class="text-slate-400 text-sm font-bold mb-3 uppercase tracking-wider">سعر صرف الليرة اليوم</p>
                    <div class="flex items-end justify-center gap-2">
                        <span class="text-5xl lg:text-6xl font-black text-white tracking-tighter">{{ number_format($egpRate, 4) }}</span>
                        <span class="text-xl font-bold text-slate-500 mb-1.5">EGP</span>
                    </div>
                    <div class="mt-5 inline-flex items-center gap-2 bg-emerald-500/10 px-3 py-1.5 rounded-full border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[11px] font-bold text-emerald-400">تم التحديث {{ $lastUpdated }}</span>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Amount Input -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-400 mb-2">المبلغ بالليرة التركية (TRY)</label>
                        <div class="relative flex items-center">
                            <input type="number" wire:model.live="amount" 
                                class="w-full bg-slate-900/50 border border-slate-700 text-white text-xl font-black rounded-2xl py-4 px-5 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all text-left shadow-inner" 
                                dir="ltr" placeholder="1000">
                            <div class="absolute right-4 flex items-center gap-2 text-slate-400 bg-slate-800 px-3 py-1 rounded-xl">
                                <span class="text-sm font-bold text-slate-300">TRY</span>
                                <span class="text-lg">🇹🇷</span>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="flex justify-center -my-3 relative z-10">
                        <div class="w-10 h-10 bg-slate-800 rounded-full flex items-center justify-center border-4 border-slate-700/50 shadow-lg text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        </div>
                    </div>

                    <!-- Result output -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-400 mb-2">المقابل بالجنيه المصري (EGP)</label>
                        <div class="relative flex items-center">
                            <div class="w-full bg-red-500/10 border border-red-500/20 text-red-400 text-2xl font-black rounded-2xl py-4 px-5 text-left truncate shadow-inner" dir="ltr">
                                {{ number_format($this->calculatedEgp, 2) }}
                            </div>
                            <div class="absolute right-4 flex items-center gap-2 text-slate-400 bg-slate-800 px-3 py-1 rounded-xl border border-slate-700">
                                <span class="text-sm font-bold text-slate-300">EGP</span>
                                <span class="text-lg">🇪🇬</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 text-center bg-slate-900/50 rounded-xl p-4 border border-slate-700/50">
                    <p class="text-[11px] text-slate-400 leading-relaxed font-medium">
                        الأسعار تقريبية وقد تتأثر بحركة السوق والعمولات. <br>تواصل معنا لتأكيد عمليتك الآن.
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>
