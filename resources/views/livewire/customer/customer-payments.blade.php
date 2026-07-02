<div class="bg-white rounded-[28px] shadow-soft border border-slate-50 overflow-hidden mt-6">
    <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-800">{{ __('messages.my_payments_list') }}</h3>
            <p class="text-xs text-slate-400 mt-1">{{ __('messages.my_payments_desc') }}</p>
        </div>
        
        <div class="flex items-center gap-2">
            <input type="date" wire:model.live="dateFrom" class="border-slate-200 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500">
            <span class="text-slate-400">-</span>
            <input type="date" wire:model.live="dateTo" class="border-slate-200 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500">
        </div>
    </div>

    <!-- Table -->
    <div class="p-0 overflow-x-auto w-full">
        <table class="w-full text-sm text-right min-w-[700px]">
            <thead class="text-[11px] text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <tr>
                    <th class="px-8 py-4 font-bold">{{ __('messages.amount') }}</th>
                    <th class="px-8 py-4 font-bold">{{ __('messages.payment_method') }}</th>
                    <th class="px-8 py-4 font-bold">{{ __('messages.date') }}</th>
                    <th class="px-8 py-4 font-bold">{{ __('messages.notes') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-8 py-4 font-bold text-emerald-600 text-lg">
                            {{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="px-8 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                {{ __('messages.method_' . $payment->payment_method) ?? __('messages.' . $payment->payment_method) ?? $payment->payment_method }}
                            </span>
                        </td>
                        <td class="px-8 py-4 text-sm text-slate-600">
                            <div class="font-bold">{{ $payment->created_at->format('Y-m-d') }}</div>
                            <div class="text-xs text-slate-400">{{ $payment->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-8 py-4 text-sm text-slate-500 max-w-xs truncate" title="{{ $payment->notes }}">
                            {{ $payment->notes ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-8 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <h3 class="mt-2 text-sm font-medium text-slate-900">{{ __('messages.no_payments_found') }}</h3>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($payments->hasPages())
        <div class="p-6 border-t border-slate-50">
            {{ $payments->links() }}
        </div>
    @endif
</div>
