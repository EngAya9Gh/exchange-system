<div class="bg-white rounded-[28px] shadow-soft border border-slate-50 overflow-hidden mt-6">
    <div class="p-8 border-b border-slate-50 flex justify-between items-center">
        <div>
            <h3 class="text-xl font-bold text-slate-800">{{ __('messages.transfer_requests_history') }}</h3>
            <p class="text-xs text-slate-400 mt-1">{{ __('messages.track_requests_status_desc') }}</p>
        </div>
    </div>

    <div class="p-0">
        <table class="w-full text-sm text-right">
            <thead class="text-[11px] text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <tr>
                    <th scope="col" class="px-8 py-4 font-bold text-center">#</th>
                    <th scope="col" class="px-8 py-4 font-bold">المستفيد</th>
                    <th scope="col" class="px-8 py-4 font-bold">{{ __('messages.requested_amount') }}</th>
                    <th scope="col" class="px-8 py-4 font-bold">تاريخ الطلب</th>
                    <th scope="col" class="px-8 py-4 font-bold text-center">{{ __('messages.request_status') }}</th>
                    <th scope="col" class="px-8 py-4 font-bold">{{ __('messages.notes') }} الإدارة</th>
                    <th scope="col" class="px-8 py-4 font-bold text-center">الإشعار</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-5 text-center">
                            <span class="text-xs font-bold text-slate-400">{{ $requests->firstItem() + $loop->index }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center ml-4 group-hover:bg-primary-50 transition">
                                    <svg class="w-5 h-5 text-slate-400 group-hover:text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ $request->recipient_name }}</div>
                                    <div class="text-[11px] text-slate-400 mt-1 font-bold">{{ $request->recipient_phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="text-base font-black text-slate-800">{{ number_format((float)$request->amount, 2) }} <span class="text-xs font-bold text-slate-400">{{ $request->currency }}</span></div>
                        </td>
                        <td class="px-8 py-5 text-xs">
                            <div class="font-bold text-slate-600">{{ $request->created_at->format('M d, Y') }}</div>
                            <div class="text-slate-400 mt-0.5">{{ $request->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="px-8 py-5 text-center">
                            @if($request->status === 'new')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 ml-1.5"></span> {{ __('messages.status_approved') }}
                                </span>
                            @elseif($request->status === 'received' || $request->status === 'paid')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-600 border border-purple-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 ml-1.5"></span> {{ __('messages.status_paid') }}
                                </span>
                            @elseif($request->status === 'rejected')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 ml-1.5"></span> {{ __('messages.status_rejected') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 ml-1.5 animate-pulse"></span> {{ __('messages.status_under_review') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-8 py-5 text-xs text-slate-500 max-w-xs truncate font-semibold">
                            {{ $request->admin_notes ?? '-' }}
                        </td>
                        <td class="px-8 py-5 text-center">
                            <a href="/receipts/{{ $request->transfer_number }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-primary-50 text-slate-600 hover:text-primary-600 transition border border-slate-200 hover:border-primary-200 shadow-sm" title="عرض أو طباعة الإشعار">
                                عرض الإشعار
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-8 py-16 text-center text-slate-400 text-sm font-bold">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            {{ __('messages.no_previous_requests_recorded') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
        <div class="p-6 border-t border-slate-50 bg-slate-50/30">
            {{ $requests->links('livewire::tailwind', data: ['scrollTo' => false]) }}
        </div>
    @endif
</div>
