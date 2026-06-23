<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center bg-white shadow-sm border-b border-gray-100 py-4 px-6">
            <h2 class="font-black text-2xl text-slate-800 leading-tight">
                {{ __('messages.profile') }}
            </h2>
            <a href="{{ auth()->user()->hasAnyRole(['Super Admin', 'Agent']) ? route('admin.dashboard') : route('dashboard') }}" class="text-sm font-bold text-primary-600 hover:text-primary-700 transition">
                {{ __('messages.return_home') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" dir="rtl">
            <!-- Telegram Link Settings -->
            @if(auth()->user()->hasAnyRole(['Super Admin', 'Agent']))
                <livewire:telegram-link />
            @endif
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
