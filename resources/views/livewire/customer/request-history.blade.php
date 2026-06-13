<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
    <h3 class="text-lg font-bold text-gray-800 border-b border-gray-50 pb-3 mb-6">سجل طلبات التحويل</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">المستفيد</th>
                    <th scope="col" class="px-6 py-3">المبلغ المطلق</th>
                    <th scope="col" class="px-6 py-3">تاريخ الطلب</th>
                    <th scope="col" class="px-6 py-3">حالة الطلب</th>
                    <th scope="col" class="px-6 py-3">ملاحظات الإدارة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-semibold text-gray-900">
                            <div>{{ $request->recipient_name }}</div>
                            <div class="text-xs text-gray-400 font-normal">{{ $request->recipient_phone }}</div>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-800">
                            {{ number_format((float)$request->amount, 2) }} {{ $request->currency }}
                        </td>
                        <td class="px-6 py-4 text-xs">
                            {{ $request->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($request->status === 'approved')
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full">مقبول</span>
                            @elseif($request->status === 'rejected')
                                <span class="px-2.5 py-1 bg-rose-100 text-rose-800 text-xs font-semibold rounded-full">مرفوض</span>
                            @else
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full">قيد المراجعة</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs max-w-xs truncate">
                            {{ $request->admin_notes ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                            لا توجد طلبات سابقة مسجلة.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
