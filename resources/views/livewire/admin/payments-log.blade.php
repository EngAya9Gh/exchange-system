<div>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-slate-800 leading-tight">
            {{ __('messages.payments_log') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-slate-100">
                
                <!-- Header & Filters -->
                <div class="p-6 bg-white border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-800">{{ __('messages.payments_list') }}</h3>
                        <p class="text-sm text-slate-500 mt-1">{{ __('messages.payments_list_desc') }}</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="{{ __('messages.search_customer') }}" class="w-full sm:w-64 pl-10 pr-4 py-2 border-slate-200 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input type="date" wire:model.live="dateFrom" class="border-slate-200 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500">
                            <span class="text-slate-400">-</span>
                            <input type="date" wire:model.live="dateTo" class="border-slate-200 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-right">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-6 py-4 text-sm font-bold text-slate-600 tracking-wider">{{ __('messages.customer') }}</th>
                                <th class="px-6 py-4 text-sm font-bold text-slate-600 tracking-wider">{{ __('messages.amount') }}</th>
                                <th class="px-6 py-4 text-sm font-bold text-slate-600 tracking-wider">{{ __('messages.payment_method') }}</th>
                                <th class="px-6 py-4 text-sm font-bold text-slate-600 tracking-wider">{{ __('messages.date') }}</th>
                                <th class="px-6 py-4 text-sm font-bold text-slate-600 tracking-wider">{{ __('messages.recorded_by') }}</th>
                                <th class="px-6 py-4 text-sm font-bold text-slate-600 tracking-wider">{{ __('messages.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse($payments as $payment)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900">{{ $payment->user->name }}</div>
                                        <div class="text-sm text-slate-500 font-mono">{{ $payment->user->phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-emerald-600">
                                        {{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                            {{ __('messages.method_' . $payment->payment_method) ?? __('messages.' . $payment->payment_method) ?? $payment->payment_method }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <div>{{ $payment->created_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-slate-400">{{ $payment->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ $payment->admin->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500 max-w-xs truncate" title="{{ $payment->notes }}">
                                        {{ $payment->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <h3 class="mt-2 text-sm font-medium text-slate-900">{{ __('messages.no_payments_found') }}</h3>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($payments->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $payments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
