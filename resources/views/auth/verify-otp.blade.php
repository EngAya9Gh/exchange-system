<x-guest-layout>
    <div dir="rtl" class="font-sans">
        <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-800">تأكيد الهوية</h2>
            <p class="text-sm text-gray-600 mt-2">
                لقد قمنا بإرسال رمز تحقق مكون من 6 أرقام إلى رقم الواتساب الخاص بك:
                <span class="font-bold text-indigo-600 block dir-ltr mt-1">
                    {{ auth()->user()->phone ?? 'غير مسجل' }}
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
            <div class="mb-4 p-3 bg-blue-50 text-blue-800 text-xs rounded-lg text-center border border-blue-100">
                الرمز للتطوير المحلي هو: <strong>{{ session('dev_code') }}</strong>
            </div>
        @endif

        {{-- POST using relative URL to bypass APP_URL misconfiguration --}}
        <form method="POST" action="{{ route('otp.verify.post') }}">
            @csrf
            <div>
                <x-input-label for="code" :value="__('رمز التحقق (OTP)')" class="text-right block mb-1" />
                <x-text-input id="code" class="block mt-1 w-full text-center tracking-widest text-xl font-bold" 
                              type="text" name="code" required autofocus autocomplete="off" placeholder="------" maxlength="6" />
                <x-input-error :messages="$errors->get('code')" class="mt-2 text-right" />
            </div>

            <div class="mt-6">
                <x-primary-button class="w-full justify-center bg-red-600 hover:bg-red-700 active:bg-red-800 focus:ring-red-500">
                    {{ __('تأكيد الرمز') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('otp.resend') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900 underline">
                إعادة إرسال الرمز
            </button>
        </form>
        
        <div class="mt-4 pt-4 border-t border-gray-100 text-center">
            <a href="{{ route('force-logout') }}" class="text-sm font-bold text-rose-600 hover:text-rose-800 transition block w-full py-2 bg-rose-50 rounded-lg border border-rose-100">
                تسجيل الخروج والعودة (انقر هنا)
            </a>
        </div>
    </div>
</x-guest-layout>
