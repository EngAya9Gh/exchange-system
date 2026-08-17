<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 font-cairo">{{ __('messages.bill_payments_history') }}</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="md:col-span-3">
            <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="{{ __('messages.search_bill_placeholder') }}" class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm bg-slate-50 font-bold">
        </div>
        
        <div class="md:col-span-1">
            <select wire:model.live="statusFilter" class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm bg-slate-50 font-bold">
                <option value="all">{{ __('messages.all_statuses') }}</option>
                <option value="pending">{{ __('messages.status_pending') }}</option>
                <option value="completed">{{ __('messages.status_completed') }}</option>
                <option value="failed">{{ __('messages.status_failed') }}</option>
                <option value="refunded">{{ __('messages.status_refunded') }}</option>
            </select>
        </div>
    </div>

    <x-card class="overflow-x-auto">
        <table class="w-full text-sm text-start text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-white/50 backdrop-blur-sm border-b border-white/40">
                <tr>
                    <th class="px-6 py-3">{{ __('messages.reference_number') }}</th>
                    <th class="px-6 py-3">{{ __('messages.customer_agent') }}</th>
                    <th class="px-6 py-3">{{ __('messages.subscriber') }}</th>
                    <th class="px-6 py-3">{{ __('messages.amount') }}</th>
                    <th class="px-6 py-3">{{ __('messages.total_deducted') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.status') }}</th>
                    <th class="px-6 py-3">{{ __('messages.date') }}</th>
                    <th class="px-6 py-3 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                    <tr class="bg-white/30 border-b border-white/30 hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 text-gray-900 font-mono font-bold">{{ $bill->tahsilat_api_islem_id }}</td>
                        <td class="px-6 py-4 font-bold text-gray-700">{{ $bill->user->name }}</td>
                        <td class="px-6 py-4 font-bold text-gray-700">{{ $bill->abone_no }}</td>
                        <td class="px-6 py-4 text-gray-900 font-bold">{{ $bill->amount }}</td>
                        <td class="px-6 py-4 text-danger-600 font-bold">{{ $bill->total_deducted }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($bill->api_status === 'pending')
                                <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2.5 py-0.5 rounded-full border border-yellow-200">{{ __('messages.status_pending') }}</span>
                            @elseif($bill->api_status === 'completed')
                                <span class="bg-success-50 text-success-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-success-200">{{ __('messages.status_completed') }}</span>
                            @else
                                <span class="bg-danger-50 text-danger-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-danger-200" title="{{ $bill->api_status_message }}">{{ __('messages.failed_refunded') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-500 text-xs">{{ $bill->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('bill-receipt.view', $bill->tahsilat_api_islem_id) }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-50 text-primary-600 hover:bg-primary-100 hover:text-primary-700 transition-colors" title="عرض الإيصال">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400 font-bold">
                            {{ __('messages.no_bills_found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
    
    <div class="mt-4">
        {{ $bills->links() }}
    </div>
</div>
