<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.62-.2-1.12-.31-1.1-.66.01-.18.27-.36.78-.55 3.07-1.34 5.11-2.22 6.13-2.65 2.92-1.21 3.52-1.42 3.91-1.42.09 0 .28.02.39.09.09.06.14.15.15.25.01.07.01.16-.01.23z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">إشعارات تلغرام</h3>
                <p class="text-sm text-slate-500 mt-1">اربط حسابك بتلغرام لاستقبال رموز التحقق (OTP) وإيصالات الحوالات فوراً.</p>
            </div>
        </div>
        
        <div>
            @if(empty($user->telegram_chat_id))
                <a href="https://t.me/{{ $botUsername }}?start={{ $user->telegram_link_token }}" target="_blank" class="px-5 py-2.5 bg-[#0088cc] hover:bg-[#0077b5] text-white rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
                    ربط الحساب الآن
                </a>
            @else
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-green-50 text-green-600 rounded-lg text-xs font-bold flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        الحساب مربوط
                    </span>
                    <button wire:click="unlink" class="text-xs font-bold text-red-500 hover:text-red-700 transition">إلغاء الربط</button>
                </div>
            @endif
        </div>
    </div>
</div>
