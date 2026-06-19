<x-guest-layout>
    <div dir="rtl" class="font-sans">
        @if (empty(auth()->user()->telegram_chat_id))
            <!-- State 1: Need to Link Telegram First -->
            <div class="mb-6 text-center">
                <div class="mx-auto w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.62-.2-1.12-.31-1.1-.66.01-.18.27-.36.78-.55 3.07-1.34 5.11-2.22 6.13-2.65 2.92-1.21 3.52-1.42 3.91-1.42.09 0 .28.02.39.09.09.06.14.15.15.25.01.07.01.16-.01.23z"/></svg>
                </div>
                <h2 class="text-2xl font-black text-slate-800">تفعيل إشعارات التلغرام</h2>
                <p class="text-sm text-slate-500 mt-3 leading-relaxed">
                    لحماية حسابك، نستخدم المصادقة الثنائية (2FA) عبر التلغرام. يرجى ربط حسابك بالبوت الرسمي الخاص بنا أولاً لاستلام رمز الدخول.
                </p>
            </div>

            <div class="mt-6 flex flex-col items-center gap-4">
                <a href="https://t.me/{{ $botUsername ?? 'exchange_adbtrk_bot' }}?start={{ auth()->user()->telegram_link_token }}" target="_blank" class="w-full py-3.5 bg-[#0088cc] hover:bg-[#0077b5] text-white rounded-xl font-bold shadow-soft transition-transform hover:-translate-y-1 flex justify-center items-center gap-2 text-lg">
                    <span>انقر هنا لربط الحساب الآن</span>
                </a>

                <p class="text-xs text-slate-400 mt-2 text-center">
                    بعد النقر والضغط على "Start" أو "بدء" في التلغرام، سيتم تحديث هذه الصفحة تلقائياً والمتابعة.
                </p>

            </div>

            <!-- Auto-polling script -->
            <script>
                setInterval(function() {
                    fetch("{{ route('otp.check_status') }}", {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.linked) {
                            window.location.reload();
                        }
                    })
                    .catch(error => console.error('Error checking status:', error));
                }, 3000);
            </script>
        @else
            <!-- State 2: OTP Verification -->
            <div class="mb-4 text-center">
                <h2 class="text-2xl font-bold text-gray-800">تأكيد الهوية</h2>
                <p class="text-sm text-slate-600 mt-2">
                    لقد قمنا بإرسال رمز تحقق مكون من 6 أرقام إلى حساب التلغرام الخاص بك:
                    <span class="font-bold text-rose-600 block mt-1">
                        {{ auth()->user()->name }}
                    </span>
                </p>
            </div>

            @if (session('status'))
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 text-xs rounded-lg text-center border border-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-3 bg-red-50 text-red-800 text-xs rounded-lg text-center border border-red-100">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('dev_code'))
                <div class="mb-4 p-3 bg-rose-50/80 backdrop-blur-sm text-rose-800 text-xs rounded-xl text-center border border-rose-200">
                    الرمز للتطوير المحلي هو: <strong class="text-rose-900">{{ session('dev_code') }}</strong>
                </div>
            @endif

            {{-- POST using relative URL to bypass APP_URL misconfiguration --}}
            <form method="POST" action="{{ route('otp.verify.post') }}" id="otp-form">
                @csrf
                <div>
                    <x-input-label for="code" :value="__('رمز التحقق (OTP)')" class="text-right block mb-1" />
                    <x-text-input id="code" class="block mt-1 w-full text-center tracking-widest text-xl font-bold" 
                                  type="text" name="code" required autofocus autocomplete="off" placeholder="------" maxlength="6" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2 text-right" />
                </div>

                <div class="mt-6">
                    <x-primary-button id="otp-submit-btn" class="w-full justify-center py-3.5 bg-gradient-to-r from-primary-600 to-rose-600 hover:from-primary-700 hover:to-rose-700 text-white rounded-xl text-lg font-black shadow-soft transition-transform hover:-translate-y-1">
                        {{ __('تأكيد الرمز') }}
                    </x-primary-button>
                </div>
            </form>

            <form method="POST" action="{{ route('otp.resend') }}" id="otp-resend-form" class="mt-4 text-center">
                @csrf
                <button type="submit" id="otp-resend-btn" class="text-sm text-black-600 hover:text-black-800 font-bold transition">
                    إعادة إرسال الرمز عبر التلغرام
                </button>
            </form>

            <script>
                document.getElementById('otp-form').addEventListener('submit', function (e) {
                    const btn = document.getElementById('otp-submit-btn');
                    if (btn) {
                        // Prevent double clicks by adding pointer-events-none and opacity
                        btn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none');
                        btn.classList.remove('hover:-translate-y-1');
                        btn.innerHTML = `
                            <svg class="animate-spin ml-2 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            جاري التحقق...
                        `;
                    }
                });

                document.getElementById('otp-resend-form').addEventListener('submit', function (e) {
                    const btn = document.getElementById('otp-resend-btn');
                    if (btn) {
                        btn.classList.add('opacity-50', 'pointer-events-none');
                        btn.innerHTML = 'جاري إعادة الإرسال...';
                    }
                });
            </script>
        @endif
        
        <div class="mt-6 pt-4 border-t border-slate-100 text-center">
            <a href="{{ route('force-logout') }}" class="text-sm font-bold text-rose-500 hover:text-rose-700 transition flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                العودة إلى صفحة الدخول
            </a>
        </div>
    </div>
</x-guest-layout>
