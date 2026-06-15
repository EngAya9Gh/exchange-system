<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight text-right">
            {{ __('لوحة التحكم - إدارة الحوالات  ') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <livewire:admin.admin-dashboard />
    </div>
</x-app-layout>