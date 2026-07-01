<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 font-cairo">{{ __('messages.deposit_requests_title') }}</h2>
    </div>

    <!-- Filters -->
    <x-card class="mb-6 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <select wire:model.live="statusFilter" class="bg-white/50 backdrop-blur-md border-white/60 focus:bg-white focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm w-full sm:w-1/4 py-2 transition-all duration-300">
                <option value="pending">{{ __('messages.status_pending') }}</option>
                <option value="approved">{{ __('messages.status_approved') }}</option>
                <option value="rejected">{{ __('messages.status_rejected') }}</option>
                <option value="all">{{ __('messages.all') }}</option>
            </select>
        </div>
    </x-card>

    @if(session('success'))
        <div class="mb-4 bg-success-50 text-success-700 p-4 rounded-lg border border-success-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Requests Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-sm text-center text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-white/50 backdrop-blur-sm border-b border-white/40">
                <tr>
                    <th class="px-6 py-3 text-center">{{ __('messages.request_number') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.user_name') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.payment_method') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.amount') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.request_date') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.status') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr class="bg-white/30 border-b border-white/30 hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900">#{{ $req->id }}</td>
                        <td class="px-6 py-4">{{ $req->user->name }}</td>
                        <td class="px-6 py-4">
                            @if($req->method == 'sham') {{ __('messages.method_sham') }} @elseif($req->method == 'uption') {{ __('messages.method_uption') }} @elseif($req->method == 'twasul') {{ __('messages.method_twasul') }} @else {{ $req->method }} @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-green-600">{{ number_format($req->amount, 2) }} TRY</td>
                        <td class="px-6 py-4" dir="ltr">{{ $req->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4">
                            @if($req->status === 'pending')
                                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.status_under_review') }}</span>
                            @elseif($req->status === 'approved')
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.status_approved') }}</span>
                            @elseif($req->status === 'rejected')
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.status_rejected') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2" x-data>
                            @if($req->status === 'pending')
                                <button type="button" @click="Swal.fire({
                                    title: '{{ __('messages.confirm_approval') }}',
                                    text: '{{ __('messages.confirm_approve_deposit_text') }}',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#10b981',
                                    cancelButtonColor: '#9ca3af',
                                    confirmButtonText: '{{ __('messages.yes_approve') }}',
                                    cancelButtonText: '{{ __('messages.cancel') }}'
                                }).then((result) => { if(result.isConfirmed) $wire.approve({{ $req->id }}) })" class="text-white bg-emerald-500 hover:bg-emerald-600 px-4 py-1.5 rounded-lg shadow-sm text-xs font-bold transition">{{ __('messages.approve') }}</button>

                                <button type="button" @click="Swal.fire({
                                    title: '{{ __('messages.reject_request') }}',
                                    text: '{{ __('messages.confirm_reject_request_text') }}',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#9ca3af',
                                    confirmButtonText: '{{ __('messages.yes_reject') }}',
                                    cancelButtonText: '{{ __('messages.cancel') }}'
                                }).then((result) => { if(result.isConfirmed) $wire.reject({{ $req->id }}) })" class="text-white bg-rose-500 hover:bg-rose-600 px-4 py-1.5 rounded-lg shadow-sm text-xs font-bold transition">{{ __('messages.reject') }}</button>
                            @else
                                <span class="text-gray-400 text-xs">{{ __('messages.none') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">{{ __('messages.no_requests_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $requests->links() }}
        </div>
    </x-card>
</div>
