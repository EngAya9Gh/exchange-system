<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 font-cairo">طلبات شحن الرصيد</h2>
    </div>

    <!-- Filters -->
    <x-card class="mb-6 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <select wire:model.live="statusFilter" class="bg-white/50 backdrop-blur-md border-white/60 focus:bg-white focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm w-full sm:w-1/4 py-2 transition-all duration-300">
                <option value="pending">قيد الانتظار</option>
                <option value="approved">مقبولة</option>
                <option value="rejected">مرفوضة</option>
                <option value="all">الكل</option>
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
                    <th class="px-6 py-3 text-center">رقم الطلب</th>
                    <th class="px-6 py-3 text-center">المستخدم</th>
                    <th class="px-6 py-3 text-center">طريقة الدفع</th>
                    <th class="px-6 py-3 text-center">المبلغ</th>
                    <th class="px-6 py-3 text-center">تاريخ الطلب</th>
                    <th class="px-6 py-3 text-center">الحالة</th>
                    <th class="px-6 py-3 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr class="bg-white/30 border-b border-white/30 hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900">#{{ $req->id }}</td>
                        <td class="px-6 py-4">{{ $req->user->name }}</td>
                        <td class="px-6 py-4">
                            @if($req->method == 'sham') شام كاش @elseif($req->method == 'uption') أوشن @elseif($req->method == 'twasul') تواصل @else {{ $req->method }} @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-green-600">{{ number_format($req->amount, 2) }} TRY</td>
                        <td class="px-6 py-4" dir="ltr">{{ $req->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4">
                            @if($req->status === 'pending')
                                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">قيد المراجعة</span>
                            @elseif($req->status === 'approved')
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">مقبول</span>
                            @elseif($req->status === 'rejected')
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">مرفوض</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2" x-data>
                            @if($req->status === 'pending')
                                <button type="button" @click="Swal.fire({
                                    title: 'تأكيد الموافقة',
                                    text: 'هل أنت متأكد من قبول الطلب وإضافة الرصيد؟',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#10b981',
                                    cancelButtonColor: '#9ca3af',
                                    confirmButtonText: 'نعم، موافق',
                                    cancelButtonText: 'إلغاء'
                                }).then((result) => { if(result.isConfirmed) $wire.approve({{ $req->id }}) })" class="text-white bg-emerald-500 hover:bg-emerald-600 px-4 py-1.5 rounded-lg shadow-sm text-xs font-bold transition">موافقة</button>

                                <button type="button" @click="Swal.fire({
                                    title: 'رفض الطلب',
                                    text: 'هل أنت متأكد من رفض هذا الطلب؟',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#9ca3af',
                                    confirmButtonText: 'نعم، ارفض',
                                    cancelButtonText: 'إلغاء'
                                }).then((result) => { if(result.isConfirmed) $wire.reject({{ $req->id }}) })" class="text-white bg-rose-500 hover:bg-rose-600 px-4 py-1.5 rounded-lg shadow-sm text-xs font-bold transition">رفض</button>
                            @else
                                <span class="text-gray-400 text-xs">لا يوجد</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">لا يوجد طلبات حالياً</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $requests->links() }}
        </div>
    </x-card>
</div>
