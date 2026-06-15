<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-black text-slate-800">
            {{ __('تسجيل الدخول') }}
        </h2>
        <p class="text-sm text-slate-500 mt-2">مرحباً بك مجدداً في نظام Teacher VC</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form action="{{ route('login') }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('البريد الإلكتروني / رقم الجوال')" class="text-slate-700 font-bold" />
            <input id="email" class="block mt-2 w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3.5 transition" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose-500" />
        </div>

        <div class="mt-5">
            <div class="flex justify-between items-center mb-2">
                <x-input-label for="password" :value="__('كلمة المرور')" class="text-slate-700 font-bold mb-0" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-bold text-primary-600 hover:text-primary-700 transition" href="{{ route('password.request') }}">
                        {{ __('نسيت الكلمة؟') }}
                    </a>
                @endif
            </div>
            <div class="relative">
                <input type="password" id="password" name="password" required autocomplete="current-password" class="block w-full bg-slate-50 text-slate-800 font-semibold rounded-xl border-none focus:ring-2 focus:ring-primary-500 pl-4 pr-12 py-3.5 transition text-right" dir="auto" />
                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 pl-3 flex items-center text-slate-400 hover:text-slate-600 transition">
                    <!-- Eye (Show) -->
                    <svg id="eye-show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <!-- Eye Slash (Hide) -->
                    <svg id="eye-hide" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.978 9.978 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-500" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-5">
            <label for="remember" class="inline-flex items-center cursor-pointer">
                <input id="remember" type="checkbox" class="rounded border-slate-300 text-primary-600 shadow-sm focus:ring-primary-500 w-5 h-5" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <span class="ms-2 text-sm font-bold text-slate-600">{{ __('تذكرني في المرات القادمة') }}</span>
            </label>
        </div>

        <div class="mt-8">
            <button type="submit" x-bind:disabled="submitting" class="w-full py-4 bg-gradient-to-r from-primary-600 to-rose-600 hover:from-primary-700 hover:to-rose-700 text-white rounded-xl font-black text-lg shadow-soft transition-transform hover:-translate-y-1 flex justify-center items-center disabled:opacity-75 disabled:cursor-wait">
                <span x-show="!submitting">{{ __('تسجيل الدخول') }}</span>
                <span x-show="submitting" class="flex items-center" style="display: none;">
                    <svg class="animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    جاري التحقق...
                </span>
            </button>
        </div>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-slate-500 font-bold">ليس لديك حساب؟ <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700 transition">سجل الآن</a></p>
        </div>
    </form>

    <script>
        function togglePassword() {
            var passInput = document.getElementById('password');
            var eyeShow = document.getElementById('eye-show');
            var eyeHide = document.getElementById('eye-hide');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeShow.classList.add('hidden');
                eyeHide.classList.remove('hidden');
            } else {
                passInput.type = 'password';
                eyeShow.classList.remove('hidden');
                eyeHide.classList.add('hidden');
            }
        }
    </script>
</x-guest-layout>
