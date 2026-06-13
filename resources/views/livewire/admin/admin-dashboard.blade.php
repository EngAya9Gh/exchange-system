<div class="min-h-screen flex bg-[#f8f9fc] font-sans text-right" dir="rtl" x-data="{ rejectModal: false, rejectId: null, rejectNotes: '' }">
    <!-- Modern Sidebar -->
    <aside class="w-[280px] bg-white border-l border-slate-100 flex-col hidden md:flex shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-20">
        <!-- Logo Area -->
        <div class="h-28 flex items-center px-8">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-bold text-xl shadow-soft">Sx</div>
            <span class="mr-3 font-black text-2xl text-slate-800 tracking-tight">SxDx Bank</span>
        </div>

        <div class="px-8 text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider">Menu</div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-2 space-y-1.5 overflow-y-auto">
            <button wire:click="$set('activeTab', 'dashboard')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'dashboard' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'dashboard' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                الرئيسية
            </button>
            <button wire:click="$set('activeTab', 'new_transfer')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'new_transfer' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'new_transfer' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                حوالة جديدة
            </button>
            <button wire:click="$set('activeTab', 'ledger')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'ledger' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'ledger' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                سجل العمليات
            </button>
            <button wire:click="$set('activeTab', 'rates')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'rates' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'rates' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                أسعار الصرف
            </button>
            <button wire:click="$set('activeTab', 'users')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'users' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'users' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                إعدادات النظام
            </button>
        </nav>

        <!-- Help Center Card -->
        <div class="p-6 mt-auto">
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-[28px] p-6 text-white text-center shadow-soft-xl relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-white/20 blur-2xl"></div>
                <div class="absolute -left-4 -bottom-4 w-16 h-16 rounded-full bg-black/10 blur-xl"></div>
                
                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm relative z-10">
                    <span class="text-primary-600 font-bold text-lg">?</span>
                </div>
                
                <h4 class="font-bold text-base mb-1 relative z-10">مركز المساعدة</h4>
                <p class="text-[11px] text-white/80 mb-4 relative z-10 leading-tight">هل تواجه مشكلة؟ يرجى التواصل معنا للمزيد من الاستفسارات.</p>
                <button class="w-full py-2.5 bg-white text-primary-600 rounded-xl text-xs font-black shadow-sm relative z-10 transition hover:bg-gray-50">الذهاب للمركز</button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
        
        <!-- Top Header -->
        <header class="h-28 bg-transparent flex items-center justify-between px-10 pt-4 z-10 relative">
            <!-- Decorative Triangles Background from SxDx image -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-20 -z-10">
                <div class="absolute top-2 left-10 text-primary-300 transform rotate-45">▲</div>
                <div class="absolute top-8 left-32 text-pink-300 transform rotate-12 text-sm">▲</div>
                <div class="absolute top-4 left-64 text-primary-200 transform -rotate-12 text-lg">▲</div>
                <div class="absolute top-12 left-96 text-orange-200 transform rotate-45 text-xs">▲</div>
            </div>

            <div>
                <h1 class="text-[28px] font-black text-slate-800 tracking-tight flex items-center">
                    @if($activeTab === 'dashboard') 
                        <span class="text-primary-600 ml-2">Welcome to SxDx.</span>
                    @endif
                    @if($activeTab === 'new_transfer') <span class="text-primary-600 ml-2">New Transfer.</span> @endif
                    @if($activeTab === 'ledger') <span class="text-primary-600 ml-2">Transactions.</span> @endif
                    @if($activeTab === 'rates') <span class="text-primary-600 ml-2">Exchange Rates.</span> @endif
                    @if($activeTab === 'users') <span class="text-primary-600 ml-2">System Settings.</span> @endif
                </h1>
                <p class="text-sm text-slate-400 font-medium mt-1">
                    @if($activeTab === 'dashboard') Hello {{ auth()->user()->name }}, welcome back! @else Manage your details here. @endif
                </p>
            </div>
            
            <div class="flex items-center space-x-6 space-x-reverse">
                <button class="text-slate-400 hover:text-primary-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                </button>
                <button class="relative text-slate-400 hover:text-primary-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <span class="absolute top-0 right-1 w-2 h-2 rounded-full bg-red-500"></span>
                </button>
                <div class="flex items-center cursor-pointer">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-orange-400 to-pink-500 shadow-sm border border-white"></div>
                </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto px-10 py-6 pb-20">

        <!-- TAB 1: General Dashboard -->
        @if ($activeTab === 'dashboard')
            
            <div class="mb-6">
                <h3 class="text-xl font-bold text-slate-800 mb-1">Quick Stats About Your Balances</h3>
                <p class="text-xs text-slate-400">نظرة عامة على أرصدة الفروع</p>
            </div>

            <!-- Balance Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- TRY Card -->
                <div class="bg-white rounded-[28px] p-6 flex flex-col items-center justify-between shadow-soft border border-slate-50 transition-transform hover:-translate-y-1">
                    <span class="text-sm text-slate-600 font-bold mb-4">TRY Balance</span>
                    
                    <!-- Decorative Circle mimicking the pie chart -->
                    <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center mb-4 relative">
                        <svg class="w-8 h-8 text-orange-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/><path d="M11 7h2v6h-2zm0 8h2v2h-2z"/></svg>
                        <div class="absolute -top-1 -right-1 bg-white rounded-full p-0.5">
                            <span class="flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span></span>
                        </div>
                    </div>

                    <h3 class="text-2xl font-black text-slate-800 mb-1 tracking-tight">{{ number_format($totalTrySent, 2) }}</h3>
                    <div class="text-[10px] font-bold text-orange-500 uppercase tracking-widest mt-4">إجمالي الليرة المصروفة</div>
                </div>

                <!-- USD Card -->
                <div class="bg-white rounded-[28px] p-6 flex flex-col items-center justify-between shadow-soft border border-slate-50 transition-transform hover:-translate-y-1">
                    <span class="text-sm text-slate-600 font-bold mb-4">USD Balance</span>
                    
                    <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mb-4 relative">
                        <svg class="w-8 h-8 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                    </div>

                    <h3 class="text-2xl font-black text-slate-800 mb-1 tracking-tight">{{ number_format($totalUsdSent, 2) }}</h3>
                    <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mt-4">إجمالي الدولار المصروف</div>
                </div>

                <!-- EUR Card -->
                <div class="bg-white rounded-[28px] p-6 flex flex-col items-center justify-between shadow-soft border border-slate-50 transition-transform hover:-translate-y-1">
                    <span class="text-sm text-slate-600 font-bold mb-4">EUR Balance</span>
                    
                    <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mb-4 relative">
                        <svg class="w-8 h-8 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M15 18.5c-2.51 0-4.68-1.42-5.76-3.5H15v-2H8.58c-.05-.33-.08-.66-.08-1s.03-.67.08-1H15v-2H9.24C10.32 6.92 12.5 5.5 15 5.5c1.61 0 3.09.59 4.23 1.57L21 4.24C19.41 2.85 17.3 2 15 2 10.3 2 6.36 5.25 5.28 9.5H3v2h2.06c-.04.33-.06.66-.06 1s.02.67.06 1H3v2h2.28C6.36 18.75 10.3 22 15 22c2.3 0 4.41-.85 6-2.24l-1.77-2.83C18.09 17.91 16.61 18.5 15 18.5z"/></svg>
                    </div>

                    <h3 class="text-2xl font-black text-slate-800 mb-1 tracking-tight">{{ number_format($totalEurSent, 2) }}</h3>
                    <div class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mt-4">إجمالي اليورو المصروف</div>
                </div>

                <!-- EGP Card (مقوم) -->
                <div class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 rounded-[28px] p-6 flex flex-col justify-between shadow-soft-xl relative overflow-hidden text-right">
                    <div class="absolute -left-6 -top-6 w-24 h-24 rounded-full bg-white/10 blur-xl"></div>
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-white/10 blur-2xl"></div>
                    
                    <div>
                        <span class="text-xs text-white/80 font-bold uppercase tracking-wider">رصيد الفودافون (المقوم)</span>
                        <h3 class="text-3xl font-black text-white mt-2 tracking-tight">{{ number_format($totalEgpPaid, 2) }}</h3>
                    </div>
                    
                    <div class="flex justify-between items-end mt-8 relative z-10">
                        <div class="text-[10px] font-bold text-white/90 uppercase tracking-widest bg-white/20 px-3 py-1.5 rounded-full backdrop-blur-sm">أداء ممتاز</div>
                        <svg class="w-12 h-12 text-white/30" fill="currentColor" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2V2h2v2h4V2h2v2zM6 8v12h12V8H6zm2 3h8v2H8v-2zm0 4h5v2H8v-2z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Incoming Customer Requests Section -->
            <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">الطلبات الواردة</h3>
                        <p class="text-xs text-slate-400 mt-1">طلبات الزبائن المعلقة لليوم</p>
                    </div>
                    <button class="px-4 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl text-xs font-bold transition">عرض الكل</button>
                </div>
                
                @if (session()->has('request_success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 text-sm font-bold rounded-xl border border-emerald-100 flex items-center">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('request_success') }}
                    </div>
                @endif
                @if (session()->has('request_error'))
                    <div class="mb-4 p-3 bg-rose-50 text-rose-800 text-sm font-bold rounded-xl border border-rose-100 flex items-center">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        {{ session('request_error') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead class="text-xs text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-4 font-semibold pb-4">معلومات المرسل</th>
                                <th class="px-4 py-4 font-semibold pb-4">معلومات المستفيد</th>
                                <th class="px-4 py-4 font-semibold pb-4">المبلغ</th>
                                <th class="px-4 py-4 font-semibold pb-4">تاريخ الطلب</th>
                                <th class="px-4 py-4 font-semibold pb-4 text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomingRequests as $req)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs ml-3 group-hover:bg-primary-50 group-hover:text-primary-600 transition">
                                                {{ mb_substr($req->user->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800">{{ $req->user->name }}</div>
                                                <div class="text-xs text-slate-400 mt-0.5">{{ $req->sender_phone }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-bold text-slate-800">{{ $req->recipient_name }}</div>
                                        <div class="text-xs text-slate-400 mt-0.5">{{ $req->recipient_phone }}</div>
                                    </td>
                                    <td class="px-4 py-4 font-black text-slate-800">
                                        {{ number_format((float)$req->amount, 2) }} <span class="text-xs text-slate-400 font-bold ml-1">{{ $req->currency }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-xs font-bold text-slate-500 bg-slate-100 inline-block px-2.5 py-1 rounded-lg">
                                            {{ $req->created_at->format('M d, Y') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center space-x-2 space-x-reverse">
                                        <button wire:click="approveRequest({{ $req->id }})" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-xl text-xs font-bold transition">
                                            قبول
                                        </button>
                                        <button x-on:click="rejectId = {{ $req->id }}; rejectModal = true" class="px-4 py-2 bg-rose-50 hover:bg-rose-500 text-rose-600 hover:text-white rounded-xl text-xs font-bold transition">
                                            رفض
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-sm font-bold">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        لا توجد طلبات معلقة حالياً
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- TAB 2: New Manual Transfer (Redesigned Exchange Screen) -->
        @if ($activeTab === 'new_transfer')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Right Side: Transfer Form (2/3 width) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-[28px] shadow-soft border border-slate-50 p-8 relative overflow-hidden">
                        <!-- Decorative subtle gradient at top right -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-bl from-primary-50 to-transparent rounded-bl-full -z-0 opacity-50"></div>
                        
                        <h3 class="text-xl font-bold text-slate-800 mb-8 flex items-center relative z-10">
                            <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center mr-3 ml-3">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </div>
                            تسجيل حوالة جديدة
                        </h3>
                        
                        @if (session()->has('transfer_success'))
                            <div class="mb-8 p-4 bg-emerald-50 text-emerald-800 font-bold rounded-2xl flex items-center relative z-10">
                                <svg class="w-5 h-5 ml-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                {{ session('transfer_success') }}
                            </div>
                        @endif

                        <form wire:submit="submitManualTransfer" class="space-y-6 relative z-10">
                            <!-- Structured Fields like the image -->
                            <div class="bg-slate-50/50 p-6 rounded-[24px] space-y-5">
                                <!-- Row 1: Recipient Name & Phone -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">اسم المستفيد</label>
                                        <input wire:model="recipient_name" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required placeholder="الاسم الكامل">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">هاتف المستفيد</label>
                                        <input wire:model="recipient_phone" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required placeholder="05xxxxxxxx">
                                    </div>
                                </div>
                                
                                <!-- Row 2: Destination & Notes -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">الجهة</label>
                                        <select wire:model="destination" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required>
                                            <option value="جميع المحافظات - فودافون مباشر">جميع المحافظات - فودافون مباشر</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">عنوان الوجهة</label>
                                        <input wire:model="address" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" placeholder="مثال: القاهرة، مدينة نصر...">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">ملاحظات إضافية</label>
                                        <input wire:model="notes" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" placeholder="مقابل كذا...">
                                    </div>
                                </div>

                                <!-- Row 3: Currency & Amount -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">العملة المسلمة</label>
                                        <select wire:model.live="source_currency" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition">
                                            <option value="TRY">ليرة تركية (TRY)</option>
                                            <option value="USD">دولار أمريكي (USD)</option>
                                            <option value="EUR">يورو (EUR)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">المبلغ المطلوب تحويله</label>
                                        <input wire:model.live="amount" type="number" step="0.01" class="w-full bg-white border-none text-primary-600 font-black text-xl rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3 shadow-sm transition" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Calculations Section -->
                            <div class="bg-gradient-to-r from-primary-50 to-indigo-50 p-6 rounded-[24px]">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div class="text-center">
                                        <span class="block text-[10px] font-bold text-primary-400 mb-1 uppercase tracking-widest">سعر الصرف</span>
                                        <span class="block font-black text-slate-800 text-lg">{{ number_format($exchange_rate, 4) }}</span>
                                    </div>
                                    <div class="text-center border-r border-primary-200/50">
                                        <span class="block text-[10px] font-bold text-rose-400 mb-1 uppercase tracking-widest">الأجور والعمولة</span>
                                        <span class="block font-black text-rose-500 text-lg">{{ number_format($commission, 2) }} <span class="text-xs">{{ $source_currency }}</span></span>
                                    </div>
                                    <div class="text-center border-r border-primary-200/50">
                                        <span class="block text-[10px] font-bold text-emerald-500 mb-1 uppercase tracking-widest">الصافي للمستفيد</span>
                                        <span class="block font-black text-emerald-600 text-2xl">{{ number_format($received_amount, 2) }} <span class="text-sm">EGP</span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Action -->
                            <div class="pt-6 flex justify-between items-center">
                                <div class="text-right">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">إجمالي المطلوب قبضه من المرسل</span>
                                    <span class="block text-3xl font-black text-slate-800">{{ number_format($total_to_pay, 2) }} <span class="text-base text-slate-400">{{ $source_currency }}</span></span>
                                </div>
                                <button type="submit" class="px-8 py-4 bg-gradient-to-r from-primary-600 to-indigo-600 hover:from-primary-700 hover:to-indigo-700 text-white rounded-2xl font-black text-lg shadow-soft transition-transform hover:-translate-y-1 flex items-center">
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    اعتماد وإرسال
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Left Side: Exchange Rates Table (1/3 width) -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-[28px] shadow-soft border border-slate-50 overflow-hidden relative">
                        <div class="bg-slate-50 px-6 py-5 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800">الأسعار الحية (Live)</h3>
                            <div class="flex items-center">
                                <span class="relative flex h-3 w-3 mr-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                            </div>
                        </div>
                        <div class="p-0">
                            <table class="w-full text-sm text-center">
                                <thead class="text-xs text-slate-400 uppercase tracking-wider">
                                    <tr>
                                        <th class="py-3 px-4 font-semibold">العملة</th>
                                        <th class="py-3 px-4 font-semibold">مقابل EGP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($exchangeRates->where('to_currency', 'EGP') as $rate)
                                    <tr class="border-t border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-4 font-bold text-slate-800 flex items-center justify-center">
                                            @if($rate->from_currency == 'TRY') <div class="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-[10px] ml-2">TR</div> @endif
                                            @if($rate->from_currency == 'USD') <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] ml-2">US</div> @endif
                                            @if($rate->from_currency == 'EUR') <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] ml-2">EU</div> @endif
                                            <span dir="ltr">{{ $rate->from_currency }}</span>
                                        </td>
                                        <td class="py-4 px-4 font-black text-primary-600 text-lg">{{ number_format($rate->rate, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    @if($exchangeRates->where('to_currency', 'EGP')->isEmpty())
                                    <tr>
                                        <td colspan="2" class="py-8 text-slate-400 text-xs font-bold">لا توجد أسعار مسجلة</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="bg-slate-50 p-4 text-center border-t border-slate-100">
                            <button wire:click="syncExchangeRates" class="text-xs font-bold text-slate-500 hover:text-primary-600 transition flex items-center justify-center mx-auto">
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                تحديث من السوق العالمي
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 3: Transfer Ledger (دفتر الحوالات) -->
        @if ($activeTab === 'ledger')
            <div class="bg-white rounded-[28px] shadow-soft border border-slate-50 overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">سجل الحركات والحوالات</h3>
                        <p class="text-xs text-slate-400 mt-1">جميع الحوالات الصادرة والواردة لجميع الفروع</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                        <div class="relative">
                            <input wire:model.live="searchQuery" type="text" placeholder="بحث بالاسم أو رقم الحوالة..." class="bg-slate-50 border-none text-sm font-bold text-slate-600 rounded-xl px-4 py-3 w-full sm:w-64 focus:ring-2 focus:ring-primary-500 transition pl-10">
                            <svg class="w-4 h-4 text-slate-400 absolute top-3.5 left-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <select wire:model.live="ledgerStatusFilter" class="bg-slate-50 border-none text-sm font-bold text-slate-600 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary-500 transition cursor-pointer">
                            <option value="all">كل الحالات</option>
                            <option value="pending">معلقة</option>
                            <option value="paid">تم التسليم</option>
                            <option value="cancelled">ملغاة</option>
                        </select>
                    </div>
                </div>

                @if (session()->has('ledger_success'))
                    <div class="m-8 mb-0 p-4 bg-emerald-50 text-emerald-800 font-bold rounded-2xl flex items-center">
                        <svg class="w-5 h-5 ml-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('ledger_success') }}
                    </div>
                @endif

                <div class="p-0 mt-4">
                    <table class="w-full text-sm text-right">
                        <thead class="text-[11px] text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-4 font-bold text-center">رقم</th>
                                <th class="px-4 py-4 font-bold text-center">المستفيد</th>
                                <th class="px-4 py-4 font-bold text-center">المبلغ</th>
                                <th class="px-4 py-4 font-bold text-center">العمولة</th>
                                <th class="px-4 py-4 font-bold text-center">تاريخ التحويل</th>
                                <th class="px-4 py-4 font-bold text-center">الحالة</th>
                                <th class="px-4 py-4 font-bold text-center">تاريخ الاستلام</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $tr)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-4 py-5 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="font-black text-slate-800 font-mono tracking-tight">{{ $tr->transfer_number }}</div>
                                            <div class="text-[10px] text-primary-600 font-bold mt-1 bg-primary-50 px-2 py-0.5 rounded inline-block uppercase tracking-widest">الرمز: {{ $tr->secret_code }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="text-sm font-bold text-slate-800">{{ $tr->recipient_name }}</div>
                                        <div class="text-[11px] text-slate-400 mt-1">من: <span class="font-bold text-slate-600">{{ $tr->sender_name ?: 'الفرع' }}</span></div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="text-sm font-black text-slate-800 flex items-center justify-center">
                                            {{ number_format((float)$tr->source_amount, 2) }} 
                                            <span class="mr-1">
                                                @if($tr->source_currency == 'TRY') 🇹🇷 @elseif($tr->source_currency == 'USD') 🇺🇸 @elseif($tr->source_currency == 'EUR') 🇪🇺 @endif
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-emerald-600 font-bold mt-1 bg-emerald-50 px-2 py-0.5 rounded inline-block">الصافي: {{ number_format((float)$tr->received_amount, 2) }} 🇪🇬</div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="text-sm font-bold text-rose-600 flex items-center justify-center">
                                            {{ number_format((float)$tr->commission, 0) }} 
                                            <span class="mr-1">
                                                @if($tr->source_currency == 'TRY') 🇹🇷 @elseif($tr->source_currency == 'USD') 🇺🇸 @elseif($tr->source_currency == 'EUR') 🇪🇺 @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-xs text-center">
                                        <div class="font-bold text-slate-600">{{ $tr->created_at->format('Y-m-d') }}</div>
                                        <div class="text-slate-400 mt-0.5">{{ $tr->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        @if($tr->status === 'paid')
                                            <span class="inline-flex items-center px-3 py-1 rounded-sm text-[10px] font-bold bg-emerald-600 text-white shadow">
                                                مستلمة
                                            </span>
                                        @elseif($tr->status === 'cancelled')
                                            <span class="inline-flex items-center px-3 py-1 rounded-sm text-[10px] font-bold bg-rose-600 text-white shadow">
                                                ملغاة
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-sm text-[10px] font-bold bg-amber-500 text-white shadow">
                                                معلقة
                                            </span>
                                        @endif
                                        <div class="mt-2 flex space-x-2 space-x-reverse justify-center">
                                            <button wire:click="viewReceipt({{ $tr->id }})" class="px-2 py-1 bg-purple-400 hover:bg-purple-500 text-white rounded text-[10px] font-bold transition flex items-center">
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                إشعار
                                            </button>
                                            @if($tr->status === 'pending')
                                            <button wire:click="payTransfer({{ $tr->id }})" class="px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-[10px] font-bold transition flex items-center">
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                وصل الاستلام
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-8 py-16 text-center text-slate-400 text-sm font-bold">
                                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        لا توجد حوالات مطابقة لمعايير البحث.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($transfers->hasPages())
                    <div class="p-6 border-t border-slate-50 bg-slate-50/30">
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 4: Exchange Rates (أسعار الصرف) -->
        @if ($activeTab === 'rates')
            <x-card class="p-6">
                <div class="flex justify-between items-center border-b border-gray-50 pb-3 mb-6">
                    <h3 class="text-lg font-bold text-gray-800">إدارة أسعار الصرف</h3>
                    <button wire:click="syncExchangeRates" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm flex items-center transition">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        تحديث الأسعار الآن من السوق العالمي
                    </button>
                </div>
                
                @if (session()->has('rate_success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 text-sm rounded-lg border border-emerald-100">
                        {{ session('rate_success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-white/50 backdrop-blur-sm border-b border-white/40">
                            <tr>
                                <th class="px-6 py-3">من عملة</th>
                                <th class="px-6 py-3">إلى عملة</th>
                                <th class="px-6 py-3">سعر الصرف الحالي</th>
                                <th class="px-6 py-3 text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exchangeRates as $rate)
                                <tr class="bg-white/30 border-b border-white/30 hover:bg-white/60 transition-colors">
                                    <td class="px-6 py-4 font-bold">{{ $rate->from_currency }}</td>
                                    <td class="px-6 py-4 font-bold">{{ $rate->to_currency }}</td>
                                    <td class="px-6 py-4">
                                        <input type="number" step="0.00001" wire:model="adjustedRates.{{ $rate->id }}" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm font-bold w-32 py-1">
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button wire:click="updateRate({{ $rate->id }})" class="px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold transition">
                                            حفظ السعر الجديد
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif

        <!-- TAB 5: User Management (إدارة المستخدمين) -->
        @if ($activeTab === 'users')
            <livewire:admin.user-management />
        @endif

    </div>

    <!-- REJECT REQUEST MODAL -->
    <div x-show="rejectModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-gray-800">سبب رفض طلب التحويل</h3>
            <textarea x-model="rejectNotes" class="w-full border-gray-300 rounded-lg text-sm" placeholder="اكتب سبب الرفض هنا..." rows="3"></textarea>
            <div class="flex justify-end space-x-2 space-x-reverse">
                <button x-on:click="rejectModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold">إلغاء</button>
                <button x-on:click="
                    $wire.rejectRequest(rejectId, rejectNotes);
                    rejectModal = false;
                    rejectNotes = '';
                " class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold">تأكيد الرفض</button>
            </div>
        </div>
    </div>

    <!-- RECEIPT VIEWER MODAL -->
    @if($showReceiptModal && $selectedTransfer)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="bg-slate-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800">إيصال الاستلام للحوالة الموثقة</h3>
                    <button wire:click="$set('showReceiptModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- PDF Preview -->
                <div class="flex-1 overflow-y-auto p-6 bg-slate-100">
                    <iframe src="{{ $receiptPdfUrl }}" class="w-full h-[380px] border border-gray-200 rounded-lg bg-white shadow-sm" frameborder="0"></iframe>
                </div>

                <!-- Action Footer Buttons -->
                <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 grid grid-cols-3 gap-2">
                    <button onclick="navigator.clipboard.writeText('{{ $selectedTransfer->transfer_number }}')" class="px-3 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold flex justify-center items-center shadow-xs">
                        نسخ رقم الحوالة
                    </button>
                    <a href="{{ $receiptPdfUrl }}" download class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold flex justify-center items-center shadow-sm text-center">
                        تحميل الـ PDF
                    </a>
                    <button onclick="window.open('{{ $receiptPdfUrl }}', '_blank')" class="px-3 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold flex justify-center items-center shadow-xs">
                        طباعة الإيصال
                    </button>
                </div>
            </div>
        </div>
    @endif
        </div>
    </main>
</div>
