<x-app-layout>
    <!-- Custom Header -->
    <div class="bg-white shadow-sm border-b border-gray-100 py-4 px-6 mb-6">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-start sm:items-center w-full gap-4 sm:gap-0" dir="rtl">
            <div class="flex items-center gap-4">
                <img src="{{ asset('logo.png?v=2') }}" alt="Logo" class="h-10 object-contain">
                <h2 class="font-black text-xl sm:text-2xl text-slate-800 leading-tight">
                    {{ __('messages.customer_portal') }}
                </h2>
            </div>
            <div class="flex flex-wrap justify-start sm:justify-end items-center gap-2 sm:gap-4 w-full sm:w-auto">
                <livewire:notification-dropdown />
                <a href="{{ route('profile') }}" title="{{ __('messages.profile' ?? 'الملف الشخصي') }}" class="text-sm font-bold text-gray-600 hover:text-primary-600 transition bg-slate-50 hover:bg-slate-100 px-4 py-2 rounded-xl flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    {{ auth()->user()->name }}
                </a>
                <livewire:logout-button />
            </div>
        </div>
    </div>

    <div class="py-12" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- User Welcome Info -->
            <div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-6 sm:p-8 relative overflow-hidden flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 sm:gap-0 transition-transform hover:-translate-y-1">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-xl sm:text-2xl font-black text-slate-800">{{ __('messages.welcome_name') }} {{ auth()->user()->name }}</h3>
                    </div>
                    <p class="text-slate-500 font-medium text-xs sm:text-sm leading-relaxed">{{ __('messages.customer_portal_desc') }}</p>
                </div>
                
                <div class="relative z-10 flex flex-col items-start sm:items-end w-full sm:w-auto bg-slate-50 sm:bg-transparent p-4 sm:p-0 rounded-xl sm:rounded-none">
                    <livewire:customer.available-balance />
                </div>
                
                <!-- Decorative abstract elements -->
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none rounded-[24px] z-0">
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-primary-100/50 rounded-full blur-2xl"></div>
                    <div class="absolute top-10 left-40 w-20 h-20 bg-rose-100/50 rounded-full blur-xl"></div>
                </div>
            </div>

            <!-- New Request Form -->
            <livewire:customer.new-transfer-request />

            <!-- Request History List -->
            <livewire:customer.request-history />
        </div>
    </div>
</x-app-layout>
