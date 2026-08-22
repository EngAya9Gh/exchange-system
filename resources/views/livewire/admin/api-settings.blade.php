<div class="max-w-4xl">
    <div class="bg-white dark:bg-gray-800 rounded-[28px] shadow-soft border border-slate-50 p-8">
        @if (session()->has('success'))
            <div class="p-4 mb-6 text-sm text-emerald-800 rounded-2xl bg-emerald-50 dark:bg-emerald-900 dark:text-emerald-400 flex items-center">
                <svg class="w-5 h-5 ml-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="saveSettings" class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">
                    {{ app()->getLocale() == 'ar' ? 'رابط الـ API' : (app()->getLocale() == 'tr' ? 'API Adresi' : 'API URL') }}
                </label>
                <input type="url" wire:model="apiUrl" class="w-full bg-slate-50 border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required dir="ltr">
                @error('apiUrl') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">
                    {{ app()->getLocale() == 'ar' ? 'كود الوكيل' : (app()->getLocale() == 'tr' ? 'Bayi Kodu' : 'Dealer Code') }}
                </label>
                <input type="text" wire:model="dealerCode" class="w-full bg-slate-50 border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required dir="ltr">
                @error('dealerCode') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">
                    {{ app()->getLocale() == 'ar' ? 'اسم المستخدم' : (app()->getLocale() == 'tr' ? 'Kullanıcı Adı' : 'Username') }}
                </label>
                <input type="text" wire:model="username" class="w-full bg-slate-50 border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required dir="ltr">
                @error('username') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div x-data="{ showPassword: false }">
                <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">
                    {{ app()->getLocale() == 'ar' ? 'كلمة المرور' : (app()->getLocale() == 'tr' ? 'Şifre' : 'Password') }}
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" wire:model="password" class="w-full bg-slate-50 border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 pl-4 pr-12 py-3.5 shadow-sm transition" required dir="ltr">
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                @error('password') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent bg-primary-600 py-4 px-4 text-sm font-bold text-white shadow-soft hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                <span wire:loading.remove wire:target="saveSettings">
                    {{ app()->getLocale() == 'ar' ? 'حفظ التعديلات' : (app()->getLocale() == 'tr' ? 'Değişiklikleri Kaydet' : 'Save Settings') }}
                </span>
                <span wire:loading wire:target="saveSettings" class="flex items-center">
                    <svg class="animate-spin ml-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ app()->getLocale() == 'ar' ? 'جاري الحفظ...' : (app()->getLocale() == 'tr' ? 'Kaydediliyor...' : 'Saving...') }}
                </span>
            </button>
        </form>
    </div>
</div>
