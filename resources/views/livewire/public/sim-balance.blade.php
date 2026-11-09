@php
    $isRtl = app()->getLocale() === 'ar';
    $queryUrl = config('services.sim.balance_check_url');
    $whatsApp = 'https://wa.me/' . preg_replace('/\D+/', '', (string) config('services.sim.support_whatsapp'))
        . '?text=' . rawurlencode(__('messages.sim_public_whatsapp_text'));
    $languages = ['ar' => 'العربية', 'tr' => 'Türkçe'];
@endphp

<div class="min-h-screen bg-slate-900 font-sans {{ $isRtl ? 'text-right' : 'text-left' }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
    style="background-image: radial-gradient(circle at top right, #1e293b 0%, #0f172a 100%);">

    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-red-600/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-1/3 -left-20 w-72 h-72 bg-rose-500/10 rounded-full blur-3xl"></div>
    </div>

    {{-- Header --}}
    <nav class="relative z-10 px-4 sm:px-6 py-5 flex justify-between items-center gap-3 max-w-6xl mx-auto">
        <a href="{{ route('sims.balance') }}" class="shrink-0">
            <img src="{{ asset('logo.png?v=2') }}" alt="Teacher VC" class="h-14 sm:h-20 object-contain drop-shadow-xl brightness-200">
        </a>
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex bg-white/10 border border-white/15 rounded-xl p-1">
                @foreach($languages as $code => $label)
                    <a href="{{ LaravelLocalization::getLocalizedURL($code, null, [], true) }}" hreflang="{{ $code }}"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-bold transition {{ app()->getLocale() === $code ? 'bg-white text-slate-900' : 'text-white/80 hover:text-white' }}">{{ $label }}</a>
                @endforeach
            </div>
            <a href="{{ route('login') }}" class="px-3 sm:px-5 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs sm:text-sm font-bold rounded-xl backdrop-blur-md transition-all whitespace-nowrap">
                {{ __('messages.sim_public_dealer') }}
            </a>
        </div>
    </nav>

    <main class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-16">
        {{-- Hero --}}
        <div class="text-center pt-4 pb-8 sm:pb-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-red-500/20 text-red-300 text-xs font-bold border border-red-500/30 mb-5" dir="ltr">📶 Tele CARD · eSIM</span>
            <h1 class="text-3xl sm:text-5xl font-black text-white leading-tight mb-4">{{ __('messages.sim_public_title') }}</h1>
            <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed font-medium">{{ __('messages.sim_public_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Embedded supplier query page --}}
            <section class="lg:col-span-2 lg:order-2">
                <div class="bg-white rounded-[28px] shadow-2xl overflow-hidden">
                    <iframe src="{{ $queryUrl }}" title="{{ __('messages.sim_public_title') }}"
                        class="w-full h-[560px] sm:h-[640px] border-0 block" loading="lazy"
                        referrerpolicy="no-referrer" allow="clipboard-write"></iframe>
                </div>
                <a href="{{ $queryUrl }}" target="_blank" rel="noopener noreferrer"
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white transition">
                    {{ __('messages.sim_public_open_new_tab') }}
                    <svg class="w-3.5 h-3.5 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </section>

            {{-- How to + call to action --}}
            <aside class="lg:order-1 space-y-6">
                <div class="bg-white/5 border border-white/10 rounded-[28px] p-6 backdrop-blur-md">
                    <h2 class="text-lg font-black text-white mb-5">{{ __('messages.sim_public_how') }}</h2>
                    <ol class="space-y-4">
                        @foreach(['sim_public_step1', 'sim_public_step2', 'sim_public_step3'] as $i => $step)
                            <li class="flex items-start gap-3">
                                <span class="shrink-0 w-7 h-7 rounded-full bg-red-500 text-white text-sm font-black flex items-center justify-center">{{ $i + 1 }}</span>
                                <span class="text-sm text-slate-300 leading-relaxed pt-0.5">{{ __("messages.{$step}") }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="rounded-[28px] p-6 bg-gradient-to-br from-red-600 to-rose-500 shadow-xl">
                    <h2 class="text-lg font-black text-white">{{ __('messages.sim_public_buy_title') }}</h2>
                    <p class="text-sm text-white/85 mt-2 leading-relaxed">{{ __('messages.sim_public_buy_desc') }}</p>
                    <a href="{{ $whatsApp }}" target="_blank" rel="noopener noreferrer"
                        class="mt-5 w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-white text-emerald-700 font-black text-sm shadow-lg hover:bg-emerald-50 transition">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2z"/></svg>
                        {{ __('messages.sim_public_whatsapp') }}
                    </a>
                </div>
            </aside>
        </div>
    </main>
</div>
