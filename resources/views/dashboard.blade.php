<x-app-layout>
    <!-- Custom Header -->
    <div class="bg-white shadow-sm border-b border-gray-100 py-4 px-6 mb-6">
        <div class="max-w-7xl mx-auto flex justify-between items-center w-full" dir="rtl">
            <h2 class="font-black text-2xl text-slate-800 leading-tight">
                {{ __('messages.customer_portal') }}
            </h2>
            <div class="flex items-center gap-4">
                <livewire:notification-dropdown />
                <div class="text-sm font-bold text-gray-600">{{ auth()->user()->name }}</div>
                <livewire:logout-button />
            </div>
        </div>
    </div>

    <div class="py-12" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- User Welcome Info -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-2xl shadow-sm p-6 text-white">
                <h3 class="text-xl font-bold">{{ __('messages.welcome_name') }}{{ auth()->user()->name }}</h3>
                <p class="text-sm text-red-100 mt-1">{{ __('messages.customer_portal_desc') }}</p>
            </div>

            <!-- Telegram Link -->
            <livewire:telegram-link />

            <!-- New Request Form -->
            <livewire:customer.new-transfer-request />

            <!-- Request History List -->
            <livewire:customer.request-history />
        </div>
    </div>
</x-app-layout>
