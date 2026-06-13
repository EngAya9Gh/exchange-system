<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight text-right">
            {{ __('لوحة التحكم - بوابة الزبائن') }}
        </h2>
    </x-slot>

    <div class="py-12" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- User Welcome Info -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-2xl shadow-sm p-6 text-white">
                <h3 class="text-xl font-bold">مرحباً بك، {{ auth()->user()->name }}!</h3>
                <p class="text-sm text-red-100 mt-1">بوابة الحوالات الخاصة بقسم حوالات فودافون كاش. يمكنك تقديم طلبات التحويل ومتابعة حالتها مباشرة.</p>
            </div>

            <!-- New Request Form -->
            <livewire:customer.new-transfer-request />

            <!-- Request History List -->
            <livewire:customer.request-history />
        </div>
    </div>
</x-app-layout>
