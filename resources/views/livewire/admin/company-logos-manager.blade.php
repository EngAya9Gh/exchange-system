<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-800 mb-4">إدارة شعارات شركات الفواتير</h2>
        <p class="text-slate-600 mb-6">قم برفع شعار لكل شركة لتسهيل تعرف المستخدمين عليها. سيتم عرض الشعار في صفحة الفواتير بدلاً من الاسم النصي فقط.</p>

        <div class="mb-6">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ابحث عن اسم الشركة لتعديل شعارها..." class="w-full px-10 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all text-sm font-medium">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl max-h-[600px] overflow-y-auto">
            <table class="w-full text-sm text-right text-slate-600">
                <thead class="text-xs text-slate-500 bg-slate-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4 font-bold border-b border-slate-200">الشركة</th>
                        <th class="px-6 py-4 font-bold border-b border-slate-200 text-center w-32">الشعار الحالي</th>
                        <th class="px-6 py-4 font-bold border-b border-slate-200 text-center w-48">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($filteredCompanies as $company)
                        @php
                            $code = $company['id'];
                            $hasLogo = isset($existingLogos[$code]);
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-700">
                                {{ $company['name'] }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($hasLogo)
                                    <div class="h-12 w-20 mx-auto flex items-center justify-center bg-white border border-slate-200 rounded p-1 relative group">
                                        <img src="{{ asset($existingLogos[$code]) }}" alt="Logo" class="max-h-full max-w-full object-contain">
                                        <button wire:click="removeLogo({{ $code }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition opacity-0 group-hover:opacity-100 shadow-sm" title="حذف الشعار">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                        لا يوجد
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-center gap-2">
                                    <label class="cursor-pointer bg-primary-50 text-primary-600 border border-primary-100 hover:bg-primary-100 hover:border-primary-200 transition rounded-lg px-3 py-1.5 text-xs font-bold w-full text-center">
                                        <span wire:loading.remove wire:target="logos.{{ $code }}">
                                            {{ $hasLogo ? 'تغيير الشعار' : 'رفع شعار' }}
                                        </span>
                                        <span wire:loading wire:target="logos.{{ $code }}">
                                            جاري الرفع...
                                        </span>
                                        <input type="file" wire:model="logos.{{ $code }}" class="hidden" accept="image/*">
                                    </label>
                                    
                                    @error('logos.'.$code)
                                        <span class="text-[10px] text-red-500 text-center w-full block">{{ $message }}</span>
                                    @enderror
                                    
                                    @if (session()->has('success_'.$code))
                                        <span class="text-[10px] text-green-600 text-center w-full block">{{ session('success_'.$code) }}</span>
                                    @endif

                                    @if(isset($logos[$code]))
                                        <button wire:click="uploadLogo({{ $code }})" class="bg-emerald-500 hover:bg-emerald-600 shadow-sm text-white font-bold py-1.5 px-3 rounded-lg text-xs transition w-full flex items-center justify-center gap-1">
                                            <span wire:loading.remove wire:target="uploadLogo({{ $code }})">حفظ</span>
                                            <span wire:loading wire:target="uploadLogo({{ $code }})">...</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-slate-500 text-sm font-medium">
                                <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                لا توجد شركات مطابقة لبحثك.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
