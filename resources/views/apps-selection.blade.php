<x-guest-layout maxWidth="sm:max-w-5xl lg:max-w-7xl" padding="px-4 py-8 sm:px-10 sm:py-10">
    <div class="w-full bg-slate-50/0 flex items-center justify-center p-0 relative overflow-hidden" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
        
        <!-- Decorative Background -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-20 -left-20 w-96 h-96 bg-primary-100/50 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-rose-100/40 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-6xl w-11/12 relative z-10">
            
            <div class="text-center mb-10">
                <h1 class="text-3xl sm:text-4xl font-black text-slate-800 tracking-tight mb-2">{{ __('messages.welcome_to') ?? 'أهلاً بك في' }} {{ config('app.name', 'نظام الصرافة') }}</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-10">
                
                <!-- Transfers System Card -->
                <a href="{{ auth()->user()->hasAnyRole(['Super Admin', 'Agent']) ? route('admin.dashboard') : route('dashboard') }}" 
                   class="group bg-white rounded-[32px] p-6 sm:p-10 shadow-soft border border-slate-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 flex flex-col items-center text-center">
                    
                    <div class="w-full h-32 sm:h-40 relative flex items-center justify-center mb-6 overflow-hidden rounded-2xl">
                        <img src="{{ asset('images/vodafone_cash_illustration.jpg') }}" alt="Vodafone Cash" class="w-full h-full object-contain bg-slate-50 group-hover:scale-105 transition-transform duration-500 rounded-xl">
                    </div>
                    
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-800">تحويلات فودافون كاش</h2>
                </a>

                <!-- Invoices System Card -->
                <a href="{{ route('invoices.dashboard') }}" 
                   class="group bg-white rounded-[32px] p-6 sm:p-10 shadow-soft border border-slate-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 flex flex-col items-center text-center">
                    
                    <div class="w-full h-32 sm:h-40 relative flex items-center justify-center mb-6 overflow-hidden rounded-2xl">
                        <img src="{{ asset('images/invoices_illustration.jpg') }}" alt="Invoices" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-800">نظام تسديد الفواتير</h2>
                </a>

            </div>

        </div>
    </div>
</x-guest-layout>
