<div class="min-h-screen flex bg-[#f8f9fc] font-sans {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" x-data="{ rejectModal: false, rejectId: null, rejectNotes: '', mobileMenuOpen: false }" wire:init="autoSyncRates">
    <!-- Mobile overlay -->
    <div x-show="mobileMenuOpen" @click="mobileMenuOpen = false" x-cloak class="fixed inset-0 bg-slate-900/50 z-30 md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Modern Sidebar -->
    <aside :class="mobileMenuOpen ? 'translate-x-0' : 'translate-x-full rtl:translate-x-full ltr:-translate-x-full md:translate-x-0 md:rtl:translate-x-0 md:ltr:translate-x-0'" class="w-[280px] shrink-0 bg-white border-x border-slate-100 flex-col flex shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-40 fixed md:sticky md:top-0 inset-y-0 right-0 rtl:right-0 ltr:left-0 transition-transform duration-300 ease-in-out h-screen">
        <!-- Logo Area -->
        <div class="h-28 flex items-center justify-between px-8">
            <div class="flex items-center">
                <img src="{{ asset('logo.png?v=2') }}" alt="Teacher VC" class="h-10 object-contain mr-2">
                <span class="mr-3 font-black text-2xl text-slate-800 tracking-tight">Teacher VC</span>
            </div>
            <!-- Close Mobile Menu -->
            <button @click="mobileMenuOpen = false" class="md:hidden text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="px-8 text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider flex justify-between items-center">
            <span>{{ __('messages.menu') }}</span>
            <!-- Auto Sync Indicator -->
            <div wire:loading wire:target="autoSyncRates" class="flex items-center text-primary-500">
                <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-2 space-y-1.5 overflow-y-auto">
            <button wire:click="$set('activeTab', 'dashboard')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'dashboard' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'dashboard' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                {{ __('messages.home') }}
            </button>
            <button wire:click="$set('activeTab', 'new_transfer')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'new_transfer' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'new_transfer' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                {{ __('messages.new_transfer_menu') }}
            </button>
            <button wire:click="$set('activeTab', 'ledger')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'ledger' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'ledger' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                {{ __('messages.ledger') }}
            </button>
            <button wire:click="$set('activeTab', 'rates')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'rates' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'rates' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                {{ __('messages.exchange_rates_menu') }}
            </button>
            <button wire:click="$set('activeTab', 'users')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'users' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'users' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                {{ __('messages.system_settings_menu') }}
            </button>
            <button wire:click="$set('activeTab', 'commissions')" class="w-full flex items-center px-4 py-3.5 rounded-2xl transition-all {{ $activeTab === 'commissions' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 font-semibold' }}">
                <svg class="w-5 h-5 ml-4 {{ $activeTab === 'commissions' ? 'text-primary-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ __('messages.commission_settings') }}
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
                
                <h4 class="font-bold text-base mb-1 relative z-10">{{ __('messages.help_center') }}</h4>
                <p class="text-[11px] text-white/80 mb-4 relative z-10 leading-tight">{{ __('messages.help_desc') }}</p>
                <button class="w-full py-2.5 bg-white text-primary-600 rounded-xl text-xs font-black shadow-sm relative z-10 transition hover:bg-gray-50">{{ __('messages.go_to_center') }}</button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
        
        <!-- Top Header -->
        <header class="bg-transparent px-4 md:px-10 pt-4 md:pt-6 pb-2">
            <div class="flex items-center justify-between">
                
                <!-- Hamburger (Mobile Only) -->
                <button @click="mobileMenuOpen = true" class="md:hidden text-slate-800 p-2 -mr-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <!-- Desktop Welcome Text -->
                <div class="hidden md:block">
                    <h1 class="text-[28px] font-black text-slate-800 tracking-tight flex items-center">
                    @if($activeTab === 'dashboard') 
                        <span class="text-primary-600 ml-2">{{ __('messages.welcome_to') }}</span>
                    @endif
                    @if($activeTab === 'new_transfer') <span class="text-primary-600 ml-2">{{ __('messages.new_transfer') }}</span> @endif
                    @if($activeTab === 'ledger') <span class="text-primary-600 ml-2">{{ __('messages.transactions') }}</span> @endif
                    @if($activeTab === 'rates') <span class="text-primary-600 ml-2">{{ __('messages.exchange_rates') }}</span> @endif
                    @if($activeTab === 'users') <span class="text-primary-600 ml-2">{{ __('messages.system_settings') }}</span> @endif
                    @if($activeTab === 'commissions') <span class="text-primary-600 ml-2">{{ __('messages.commissions') }}</span> @endif
                    </h1>
                    <p class="text-sm text-slate-400 font-medium mt-1">
                        @if($activeTab === 'dashboard') {{ __('messages.hello_welcome_back', ['name' => auth()->user()->name]) }} @else {{ __('messages.manage_details_here') }} @endif
                    </p>
                </div>
                
                <!-- Right Side Icons -->
                <div class="flex items-center space-x-2 md:space-x-6 space-x-reverse">
                    <button class="text-slate-400 hover:text-primary-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    </button>
                    
                    <livewire:notification-dropdown />

                    <a href="{{ route('profile') }}" title="{{ __('messages.profile') }}" class="flex items-center cursor-pointer gap-3 hover:opacity-80 transition bg-slate-50 hover:bg-slate-100 px-3 py-2 rounded-xl border border-slate-100">
                        <div class="text-left hidden md:block" dir="ltr">
                            <div class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] font-bold text-slate-400 mt-0.5">{{ __('messages.system_admin') }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-orange-400 to-pink-500 shadow-sm border border-white flex items-center justify-center text-white font-bold">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </a>

                    <!-- Language Switcher -->
                    <div class="relative" x-data="{ openLang: false }" wire:ignore>
                        <button @click="openLang = !openLang" @click.away="openLang = false" class="px-3 py-2 text-slate-500 hover:text-primary-600 hover:bg-primary-50 transition-all flex items-center gap-2 rounded-xl border border-slate-100 shadow-sm bg-white">
                            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @php
                                $currentLocale = app()->getLocale();
                                $flags = ['ar' => '🇸🇦', 'en' => '🇬🇧', 'tr' => '🇹🇷'];
                            @endphp
                            <span class="text-sm">{{ $flags[$currentLocale] ?? '🌐' }}</span>
                            <span class="text-xs font-bold uppercase">{{ $currentLocale }}</span>
                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        
                        <div x-show="openLang" style="display: none;" class="absolute left-0 mt-2 w-36 bg-white border border-slate-100 rounded-xl shadow-lg z-50 overflow-hidden py-1">
                            @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                                <a rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}" class="flex items-center gap-3 px-4 py-2 text-sm font-bold transition-colors {{ app()->getLocale() === $localeCode ? 'bg-primary-50 text-primary-600' : 'text-slate-600 hover:bg-slate-50 hover:text-primary-500' }}">
                                    <span class="text-lg">{{ $flags[$localeCode] ?? '🌐' }}</span>
                                    <span>{{ $properties['native'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Logout Button -->
                    <button wire:click="logout" class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition flex items-center gap-2 mr-2" title="{{ __('messages.logout_button') }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span class="text-sm font-bold hidden sm:inline">{{ __('messages.logout') }}</span>
                    </button>
                </div>
            </div>

            <!-- Mobile Welcome Text (Drops below header on small screens) -->
            <div class="md:hidden mt-6 mb-2 px-2">
                <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center flex-wrap gap-1">
                    @if($activeTab === 'dashboard') 
                        <span class="text-primary-600">{{ __('messages.welcome_to') }}</span>
                    @endif
                    @if($activeTab === 'new_transfer') <span class="text-primary-600">{{ __('messages.new_transfer') }}</span> @endif
                    @if($activeTab === 'ledger') <span class="text-primary-600">{{ __('messages.transactions') }}</span> @endif
                    @if($activeTab === 'rates') <span class="text-primary-600">{{ __('messages.exchange_rates') }}</span> @endif
                    @if($activeTab === 'users') <span class="text-primary-600">{{ __('messages.system_settings') }}</span> @endif
                    @if($activeTab === 'commissions') <span class="text-primary-600">{{ __('messages.commissions') }}</span> @endif
                </h1>
                <p class="text-sm text-slate-400 font-medium mt-1">
                    @if($activeTab === 'dashboard') {{ __('messages.hello_welcome_back', ['name' => auth()->user()->name]) }} @else {{ __('messages.manage_details_here') }} @endif
                </p>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto px-4 md:px-10 py-6 pb-20">
            <!-- Telegram Link (Only show if not linked) -->
            @if(empty(auth()->user()->telegram_chat_id))
                <livewire:telegram-link />
            @endif

        <!-- TAB 1: General Dashboard -->
        @if ($activeTab === 'dashboard')
            
            <div class="mb-6">
                <h3 class="text-xl font-bold text-slate-800 mb-1">{{ __('messages.quick_stats') }}</h3>
                <p class="text-xs text-slate-400">{{ __('messages.branches_balances_overview') }}</p>
            </div>

            <!-- Balance Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
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
                    <div class="text-[10px] font-bold text-orange-500 uppercase tracking-widest mt-4">{{ __('messages.total_try_spent') }}</div>
                </div>

                <!-- USD Card -->
                <div class="bg-white rounded-[28px] p-6 flex flex-col items-center justify-between shadow-soft border border-slate-50 transition-transform hover:-translate-y-1">
                    <span class="text-sm text-slate-600 font-bold mb-4">USD Balance</span>
                    
                    <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mb-4 relative">
                        <svg class="w-8 h-8 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                    </div>

                    <h3 class="text-2xl font-black text-slate-800 mb-1 tracking-tight">{{ number_format($totalUsdSent, 2) }}</h3>
                    <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mt-4">{{ __('messages.total_usd_spent') }}</div>
                </div>

                <!-- EUR Card -->
                <div class="bg-white rounded-[28px] p-6 flex flex-col items-center justify-between shadow-soft border border-slate-50 transition-transform hover:-translate-y-1">
                    <span class="text-sm text-slate-600 font-bold mb-4">EUR Balance</span>
                    
                    <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mb-4 relative">
                        <svg class="w-8 h-8 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M15 18.5c-2.51 0-4.68-1.42-5.76-3.5H15v-2H8.58c-.05-.33-.08-.66-.08-1s.03-.67.08-1H15v-2H9.24C10.32 6.92 12.5 5.5 15 5.5c1.61 0 3.09.59 4.23 1.57L21 4.24C19.41 2.85 17.3 2 15 2 10.3 2 6.36 5.25 5.28 9.5H3v2h2.06c-.04.33-.06.66-.06 1s.02.67.06 1H3v2h2.28C6.36 18.75 10.3 22 15 22c2.3 0 4.41-.85 6-2.24l-1.77-2.83C18.09 17.91 16.61 18.5 15 18.5z"/></svg>
                    </div>

                    <h3 class="text-2xl font-black text-slate-800 mb-1 tracking-tight">{{ number_format($totalEurSent, 2) }}</h3>
                    <div class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mt-4">{{ __('messages.total_eur_spent') }}</div>
                </div>

                <!-- Profits / Commissions Card -->
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-[28px] p-6 flex flex-col justify-between shadow-soft border border-slate-50 transition-transform hover:-translate-y-1 relative overflow-hidden text-right">
                    <div class="absolute -left-10 -bottom-10 w-32 h-32 rounded-full bg-white/10 blur-2xl"></div>
                    
                    <div>
                        <span class="text-xs text-white/80 font-bold uppercase tracking-wider">{{ __('messages.total_profits') }}</span>
                        <h3 class="text-3xl font-black text-white mt-2 tracking-tight">{{ number_format($totalCommissions, 2) }}</h3>
                    </div>
                    
                    <div class="flex justify-between items-end mt-8 relative z-10">
                        <div class="text-[10px] font-bold text-white/90 uppercase tracking-widest bg-white/20 px-3 py-1.5 rounded-full backdrop-blur-sm">TRY</div>
                        <svg class="w-10 h-10 text-white/30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                </div>

                <!-- EGP Card (مقوم) -->
                <div class="bg-gradient-to-br from-primary-700 via-primary-600 to-rose-500 rounded-[28px] p-6 flex flex-col justify-between shadow-soft-xl relative overflow-hidden text-right">
                    <div class="absolute -left-6 -top-6 w-24 h-24 rounded-full bg-white/10 blur-xl"></div>
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-white/10 blur-2xl"></div>
                    
                    <div>
                        <span class="text-xs text-white/80 font-bold uppercase tracking-wider">{{ __('messages.vodafone_balance') }}</span>
                        <h3 class="text-3xl font-black text-white mt-2 tracking-tight">{{ number_format($totalEgpPaid, 2) }}</h3>
                    </div>
                    
                    <div class="flex justify-between items-end mt-8 relative z-10">
                        <div class="text-[10px] font-bold text-white/90 uppercase tracking-widest bg-white/20 px-3 py-1.5 rounded-full backdrop-blur-sm">{{ __('messages.excellent_performance') }}</div>
                        <svg class="w-12 h-12 text-white/30" fill="currentColor" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2V2h2v2h4V2h2v2zM6 8v12h12V8H6zm2 3h8v2H8v-2zm0 4h5v2H8v-2z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Incoming Customer Requests Section -->
            <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">{{ __('messages.incoming_requests') }}</h3>
                        <p class="text-xs text-slate-400 mt-1">{{ __('messages.pending_customer_requests_today') }}</p>
                    </div>
                    <button class="px-4 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl text-xs font-bold transition">{{ __('messages.view_all') }}</button>
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
                                <th class="px-4 py-4 font-semibold pb-4 text-center">#</th>
                                <th class="px-4 py-4 font-semibold pb-4">{{ __('messages.sender_info') }}</th>
                                <th class="px-4 py-4 font-semibold pb-4">{{ __('messages.recipient_info') }}</th>
                                <th class="px-4 py-4 font-semibold pb-4">{{ __('messages.amount') }}</th>
                                <th class="px-4 py-4 font-semibold pb-4">{{ __('messages.request_date') }}</th>
                                <th class="px-4 py-4 font-semibold pb-4 text-center">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomingRequests as $req)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-xs font-bold text-slate-400">{{ $incomingRequests->firstItem() + $loop->index }}</span>
                                    </td>
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
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex flex-col gap-2 w-full max-w-[120px] mx-auto">
                                            <button wire:click="payTransfer({{ $req->id }})" wire:loading.attr="disabled" class="w-full px-4 py-2 bg-emerald-50 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-xl text-xs font-bold transition disabled:opacity-75 disabled:cursor-wait flex items-center justify-center">
                                                <span wire:loading.remove wire:target="payTransfer({{ $req->id }})">{{ __('messages.receipt') }}</span>
                                                <span wire:loading wire:target="payTransfer({{ $req->id }})" class="flex items-center">
                                                    <svg class="animate-spin ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    يُحمل...
                                                </span>
                                            </button>
                                            <button x-on:click="rejectId = {{ $req->id }}; rejectModal = true" class="w-full px-4 py-2 bg-rose-50 hover:bg-rose-500 text-rose-600 hover:text-white rounded-xl text-xs font-bold transition flex items-center justify-center">
                                                {{ __('messages.reject') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-sm font-bold">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        {{ __('messages.no_pending_requests') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($incomingRequests->hasPages())
                    <div class="p-4 border-t border-slate-50 bg-slate-50/30">
                        {{ $incomingRequests->links('livewire::tailwind', data: ['scrollTo' => false]) }}
                    </div>
                @endif
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
                        
                        <h3 class="text-xl font-bold text-slate-800 mb-8 flex items-center relative">
                            <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center mr-3 ml-3">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </div>
                            {{ __('messages.register_btn') }} {{ __('messages.new_transfer_menu') }}
                        </h3>
                        
                        @if (session()->has('transfer_success'))
                            <div class="mb-8 p-4 bg-emerald-50 text-emerald-800 font-bold rounded-2xl flex items-center relative">
                                <svg class="w-5 h-5 ml-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                {{ session('transfer_success') }}
                            </div>
                        @endif

                        <form wire:submit="submitManualTransfer" class="space-y-6 relative">
                            <!-- Structured Fields like the image -->
                            <div class="bg-slate-50/50 p-6 rounded-[24px] space-y-5">
                                <!-- Row 1: Recipient Name & Phone -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ __('messages.recipient_name') }}</label>
                                        <input wire:model="recipient_name" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required placeholder="{{ __('messages.full_name') }}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ __('messages.recipient_phone') }}</label>
                                        <input wire:model="recipient_phone" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" required placeholder="05xxxxxxxx">
                                    </div>
                                </div>
                                
                                <!-- Row 2: Destination & Notes -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ __('messages.destination') }}</label>
                                        <select wire:model="destination" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 pr-4 pl-10 py-3.5 shadow-sm transition bg-left" required>
                                            <option value="{{ __('messages.all_governorates_vodafone') }}">{{ __('messages.all_governorates_vodafone') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ __('messages.destination_address') }}</label>
                                        <input wire:model="address" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" placeholder="{{ __('messages.address_example') }}">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-5">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ __('messages.additional_notes') }}</label>
                                        <input wire:model="notes" type="text" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3.5 shadow-sm transition" placeholder="{{ __('messages.notes_example') }}">
                                    </div>
                                </div>

                                <!-- Row 3: Currency, Amount & Wages -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ __('messages.delivered_currency') }}</label>
                                        <select wire:model.live="source_currency" class="w-full bg-white border-none text-slate-800 font-bold rounded-xl focus:ring-2 focus:ring-primary-500 pr-4 pl-10 py-3.5 shadow-sm transition bg-left">
                                            <option value="TRY">{{ __('messages.turkish_lira') }}</option>
                                            <option value="USD">{{ __('messages.us_dollar') }}</option>
                                            <option value="EUR">{{ __('messages.euro') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ __('messages.amount') }} {{ __('messages.required_to_transfer') }}</label>
                                        <input wire:model.live="amount" type="number" step="0.01" class="w-full bg-white border-none text-primary-600 font-black text-xl rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3 shadow-sm transition" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">
                                            {{ __('messages.fees') }} ({{ $source_currency }})
                                            @if($enableAutomatedCommissions)
                                                <span class="text-emerald-500 text-[10px] mr-1">({{ __('messages.automated') }})</span>
                                            @endif
                                        </label>
                                        <input wire:model.live="manual_fee" type="number" step="0.01" 
                                            class="w-full border-none font-black text-xl rounded-xl focus:ring-2 focus:ring-primary-500 px-4 py-3 shadow-sm transition {{ $enableAutomatedCommissions ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-rose-600' }}" 
                                            @if($enableAutomatedCommissions) disabled @endif
                                            placeholder="{{ $enableAutomatedCommissions ? __('messages.automatic_by_tiers') : __('messages.enter_fees_value') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Calculations Section -->
                            <div class="bg-gradient-to-r from-primary-50 to-rose-50 p-6 rounded-[24px]">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div class="text-center">
                                        <span class="block text-[10px] font-bold text-primary-400 mb-1 uppercase tracking-widest">{{ __('messages.exchange_rate_label') }}</span>
                                        <span class="block font-black text-slate-800 text-lg">{{ number_format($exchange_rate, 4) }}</span>
                                    </div>
                                    <div class="text-center border-r border-primary-200/50">
                                        <span class="block text-[10px] font-bold text-rose-400 mb-1 uppercase tracking-widest">{{ __('messages.fees') }} {{ __('messages.and_separator') }} {{ __('messages.commission_label') }}</span>
                                        <span class="block font-black text-rose-500 text-lg">{{ number_format($commission, 2) }} <span class="text-xs">{{ $source_currency }}</span></span>
                                    </div>
                                    <div class="text-center border-r border-primary-200/50">
                                        <span class="block text-[10px] font-bold text-emerald-500 mb-1 uppercase tracking-widest">{{ __('messages.net_for_recipient') }}</span>
                                        <span class="block font-black text-emerald-600 text-2xl">{{ number_format($received_amount, 2) }} <span class="text-sm">EGP</span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Action -->
                            <div class="pt-6 flex justify-between items-center">
                                <div class="text-right">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('messages.total_to_collect') }}</span>
                                    <span class="block text-3xl font-black text-slate-800">{{ number_format($total_to_pay, 2) }} <span class="text-base text-slate-400">{{ $source_currency }}</span></span>
                                </div>
                                <button type="submit" wire:loading.attr="disabled" wire:target="submitManualTransfer" class="px-8 py-4 bg-gradient-to-r from-primary-600 to-rose-600 hover:from-primary-700 hover:to-rose-700 text-white rounded-2xl font-black text-lg shadow-soft transition-transform hover:-translate-y-1 flex items-center justify-center disabled:opacity-75 disabled:cursor-wait">
                                    <span wire:loading.remove wire:target="submitManualTransfer" class="flex items-center">
                                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                        {{ __('messages.approve_and_send') }}
                                    </span>
                                    <span wire:loading wire:target="submitManualTransfer" class="flex items-center">
                                        <svg class="animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        جاري الاعتماد والتحويل...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Left Side: Exchange Rates Table (1/3 width) -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-[28px] shadow-soft border border-slate-50 overflow-hidden relative">
                        <div class="bg-slate-50 px-6 py-5 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800">{{ __('messages.live_rates') }}</h3>
                            <div class="flex items-center">
                                <span class="relative flex h-3 w-3 mr-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                            </div>
                        </div>
                        <div class="p-0 overflow-x-auto">
                            <table class="w-full text-sm text-center">
                                <thead class="text-xs text-slate-400 uppercase tracking-wider">
                                    <tr>
                                        <th class="py-3 px-4 font-semibold">{{ __('messages.currency_label') }}</th>
                                        <th class="py-3 px-4 font-semibold">{{ __('messages.against_egp') }}</th>
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
                                        <td colspan="2" class="py-8 text-slate-400 text-xs font-bold">{{ __('messages.no_recorded_rates') }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="bg-slate-50 p-4 text-center border-t border-slate-100">
                            @if (session()->has('rate_success'))
                                <div class="mb-3 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-lg p-2">
                                    {{ session('rate_success') }}
                                </div>
                            @endif
                            <button wire:click="syncExchangeRates" class="text-xs font-bold text-slate-500 hover:text-primary-600 transition flex items-center justify-center mx-auto" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="syncExchangeRates" class="flex items-center">
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    {{ __('messages.update_from_global_market') }}
                                </span>
                                <span wire:loading wire:target="syncExchangeRates" class="flex items-center text-primary-600">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ __('messages.updating') }}
                                </span>
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
                        <h3 class="text-xl font-bold text-slate-800">{{ __('messages.ledger_title') }}</h3>
                        <p class="text-xs text-slate-400 mt-1">{{ __('messages.all_transfers_desc') }}</p>
                    </div>
                    
                    <div class="flex flex-col lg:flex-row gap-3 w-full lg:w-auto mt-4 md:mt-0">
                        <div class="relative w-full lg:w-auto">
                            <input wire:model.live="searchQuery" type="text" placeholder="{{ __('messages.search_placeholder') }}" class="bg-slate-50 border-none text-sm font-bold text-slate-600 rounded-xl px-4 py-3 w-full lg:w-48 focus:ring-2 focus:ring-primary-500 transition pl-10">
                            <svg class="w-4 h-4 text-slate-400 absolute top-3.5 left-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        
                        <select wire:model.live="ledgerCurrencyFilter" class="bg-slate-50 border-none text-sm font-bold text-slate-600 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary-500 transition cursor-pointer w-full lg:w-auto">
                            <option value="all">{{ __('messages.all_currencies') }}</option>
                            <option value="TRY">{{ __('messages.turkish_lira') }}</option>
                            <option value="USD">{{ __('messages.us_dollar') }}</option>
                            <option value="EUR">{{ __('messages.euro') }}</option>
                        </select>
                        
                        <select wire:model.live="ledgerDateFilter" class="bg-slate-50 border-none text-sm font-bold text-slate-600 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary-500 transition cursor-pointer w-full lg:w-auto">
                            <option value="all">{{ __('messages.all_times') }}</option>
                            <option value="today">{{ __('messages.today') }}</option>
                            <option value="this_week">{{ __('messages.this_week') }}</option>
                            <option value="this_month">{{ __('messages.this_month') }}</option>
                        </select>

                        <select wire:model.live="ledgerStatusFilter" class="bg-slate-50 border-none text-sm font-bold text-slate-600 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary-500 transition cursor-pointer w-full lg:w-auto">
                            <option value="all">{{ __('messages.all_statuses') }}</option>
                            <option value="pending">{{ __('messages.status_pending') }}</option>
                            <option value="paid">{{ __('messages.status_paid') }}</option>
                            <option value="cancelled">{{ __('messages.status_cancelled') }}</option>
                        </select>
                    </div>
                </div>

                @if (session()->has('ledger_success'))
                    <div class="m-8 mb-0 p-4 bg-emerald-50 text-emerald-800 font-bold rounded-2xl flex items-center">
                        <svg class="w-5 h-5 ml-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('ledger_success') }}
                    </div>
                @endif

                <div class="p-0 mt-4 overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead class="text-[11px] text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-4 font-bold text-center">#</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.number') }}</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.recipient') }}</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.recipient_phone_number') }}</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.amount') }}</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.commission_label') }}</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.transfer_date') }}</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.status') }}</th>
                                <th class="px-4 py-4 font-bold text-center">{{ __('messages.receipt_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $tr)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-4 py-5 text-center">
                                        <span class="text-xs font-bold text-slate-400">{{ $transfers->firstItem() + $loop->index }}</span>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="font-black text-slate-800 font-mono tracking-tight">{{ $tr->transfer_number }}</div>
                                            <div class="text-[10px] text-primary-600 font-bold mt-1 bg-primary-50 px-2 py-0.5 rounded inline-block uppercase tracking-widest">{{ __('messages.code_label') }} {{ $tr->secret_code }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="text-sm font-bold text-slate-800">{{ $tr->recipient_name }}</div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="text-[11px] font-bold text-slate-600" dir="ltr">{{ $tr->recipient_phone }}</div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="text-sm font-black text-slate-800 flex items-center justify-center">
                                            {{ number_format((float)$tr->amount, 2) }} 
                                            <span class="mr-1">
                                                @if($tr->currency == 'TRY') 🇹🇷 @elseif($tr->currency == 'USD') 🇺🇸 @elseif($tr->currency == 'EUR') 🇪🇺 @endif
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-emerald-600 font-bold mt-1 bg-emerald-50 px-2 py-0.5 rounded inline-block">{{ __('messages.net_label') }} {{ number_format((float)$tr->received_amount, 2) }} 🇪🇬</div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <div class="text-sm font-bold text-rose-600 flex items-center justify-center">
                                            {{ number_format((float)$tr->commission, 0) }} 
                                            <span class="mr-1">
                                                @if($tr->currency == 'TRY') 🇹🇷 @elseif($tr->currency == 'USD') 🇺🇸 @elseif($tr->currency == 'EUR') 🇪🇺 @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-xs text-center">
                                        <div class="font-bold text-slate-600">{{ $tr->created_at->format('Y-m-d') }}</div>
                                        <div class="text-slate-400 mt-0.5">{{ $tr->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        @if($tr->status === 'received')
                                            <span class="inline-flex items-center px-3 py-1 rounded-sm text-[10px] font-bold bg-emerald-600 text-white shadow">
                                                {{ __('messages.status_received') }}
                                            </span>
                                        @elseif($tr->status === 'cancelled')
                                            <span class="inline-flex items-center px-3 py-1 rounded-sm text-[10px] font-bold bg-rose-600 text-white shadow">
                                                {{ __('messages.status_cancelled') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-sm text-[10px] font-bold bg-amber-500 text-white shadow">
                                                {{ __('messages.status_pending') }}
                                            </span>
                                        @endif
                                        <div class="mt-2 flex space-x-2 space-x-reverse justify-center">
                                            <button wire:click="viewReceipt({{ $tr->id }})" wire:loading.attr="disabled" class="px-2 py-1 bg-purple-400 hover:bg-purple-500 text-white rounded text-[10px] font-bold transition flex items-center disabled:opacity-75 disabled:cursor-wait">
                                                <span wire:loading.remove wire:target="viewReceipt({{ $tr->id }})" class="flex items-center">
                                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    {{ __('messages.status_notification') }}
                                                </span>
                                                <span wire:loading wire:target="viewReceipt({{ $tr->id }})" class="flex items-center">
                                                    <svg class="animate-spin ml-1 h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    يُحمل...
                                                </span>
                                            </button>
                                            @if($tr->status === 'new' || $tr->status === 'pending')
                                            <button wire:click="payTransfer({{ $tr->id }})" wire:loading.attr="disabled" class="px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-[10px] font-bold transition flex items-center disabled:opacity-75 disabled:cursor-wait">
                                                <span wire:loading.remove wire:target="payTransfer({{ $tr->id }})" class="flex items-center">
                                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    {{ __('messages.receipt') }}
                                                </span>
                                                <span wire:loading wire:target="payTransfer({{ $tr->id }})" class="flex items-center">
                                                    <svg class="animate-spin ml-1 h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    يُحمل...
                                                </span>
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
                                        {{ __('messages.no_transfers_matching') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                                @if($transfers->hasPages())
                    <div class="p-6 border-t border-slate-50 bg-slate-50/30">
                        {{ $transfers->links('livewire::tailwind', data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 4: Exchange Rates ({{ __('messages.exchange_rates_menu') }}) -->
        @if ($activeTab === 'rates')
            <x-card class="p-6">
                <div class="flex justify-between items-center border-b border-gray-50 pb-3 mb-6">
                    <h3 class="text-lg font-bold text-gray-800">{{ __('messages.manage_label') }}{{ __('messages.exchange_rates_menu') }}</h3>
                    <button wire:click="syncExchangeRates" wire:loading.attr="disabled" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-bold shadow-sm flex items-center transition disabled:opacity-50">
                        <svg wire:loading.remove wire:target="syncExchangeRates" class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <svg wire:loading wire:target="syncExchangeRates" class="animate-spin w-4 h-4 ml-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span wire:loading.remove wire:target="syncExchangeRates">{{ __('messages.update_prices_now') }}</span>
                        <span wire:loading wire:target="syncExchangeRates">{{ __('messages.updating') }}</span>
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
                                <th class="px-6 py-3">{{ __('messages.from_currency_header') }}</th>
                                <th class="px-6 py-3">{{ __('messages.to_currency_header') }}</th>
                                <th class="px-6 py-3">{{ __('messages.exchange_rate_label') }}{{ __('messages.current_label') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('messages.actions') }}</th>
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
                                            {{ __('messages.save_new_rate') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif

        <!-- TAB 5: User Management ({{ __('messages.manage_label') }}{{ __('messages.users_title') }}) -->
        @if ($activeTab === 'users')
            <livewire:admin.user-management />
        @endif

        <!-- TAB 6: Commission Management ({{ __('messages.commission_settings') }}) -->
        @if ($activeTab === 'commissions')
            <div class="space-y-6">
                @if (session('commission_success'))
                    <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl font-bold flex items-center">
                        <svg class="w-5 h-5 mr-2 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('commission_success') }}
                    </div>
                @endif

                <!-- Automated Commissions Toggle -->
                <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-1">{{ __('messages.automated_commissions_by_tiers') }}</h3>
                        <p class="text-sm text-slate-500">{{ __('messages.automated_commissions_desc') }}</p>
                    </div>
                    <button wire:click="toggleAutomatedCommissions" class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 transition-colors duration-200 ease-in-out {{ $enableAutomatedCommissions ? 'bg-primary-600' : 'bg-slate-200' }}" role="switch" aria-checked="{{ $enableAutomatedCommissions ? 'true' : 'false' }}">
                        <span class="sr-only">Toggle automated commissions</span>
                        <span aria-hidden="true" class="pointer-events-none absolute h-full w-full rounded-md bg-white opacity-0 transition-opacity duration-200 ease-in-out"></span>
                        <span aria-hidden="true" class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $enableAutomatedCommissions ? '-translate-x-3' : 'translate-x-3' }}"></span>
                    </button>
                </div>

                <!-- Default Commission Settings -->
                <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">{{ __('messages.default_commission_percentage') }}</h3>
                    <p class="text-sm text-slate-500 mb-6">{{ __('messages.default_commission_desc') }}</p>
                    
                    <div class="flex items-end gap-4 max-w-sm">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.percentage') }}</label>
                            <input type="number" step="0.01" wire:model="defaultCommission" class="w-full bg-slate-50 text-slate-800 font-bold rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3">
                        </div>
                        <button wire:click="saveDefaultCommission" class="py-3 px-6 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-sm transition">
                            {{ __('messages.save_percentage') }}
                        </button>
                    </div>
                </div>

                <!-- Add New Tier -->
                <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">{{ __('messages.add_new_commission_tier') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.from_amount_try') }}</label>
                            <input type="number" wire:model="tierMinAmount" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                            @error('tierMinAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.to_amount_try') }}</label>
                            <input type="number" wire:model="tierMaxAmount" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                            @error('tierMaxAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.type_label') }} {{ __('messages.commission_label') }}</label>
                            <select wire:model="tierCommissionType" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                                <option value="fixed">{{ __('messages.fixed_amount_try') }}</option>
                                <option value="percentage">{{ __('messages.percentage_symbol') }}</option>
                            </select>
                            @error('tierCommissionType') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">{{ __('messages.value_label') }}</label>
                            <input type="number" step="0.01" wire:model="tierCommissionValue" class="w-full bg-slate-50 rounded-xl border-none focus:ring-2 focus:ring-primary-500 px-4 py-3 text-sm">
                            @error('tierCommissionValue') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button wire:click="saveTier" class="py-3 px-8 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold shadow-sm transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            {{ __('messages.add_tier_btn') }}
                        </button>
                    </div>
                </div>

                <!-- Tiers List -->
                <div class="bg-white rounded-3xl p-6 shadow-soft border border-slate-50 {{ !$enableAutomatedCommissions ? 'opacity-50 pointer-events-none' : '' }}">
                    <h3 class="text-lg font-bold text-slate-800 mb-6">{{ __('messages.current_tiers') }}</h3>
                    
                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.range_label') }}</th>
                                    <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.type_label') }} {{ __('messages.commission_label') }}</th>
                                    <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.value_label') }}</th>
                                    <th class="px-6 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-wider">{{ __('messages.actions_label') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @forelse($commissionTiers as $tier)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-slate-800">{{ number_format($tier->min_amount, 2) }} - {{ number_format($tier->max_amount, 2) }} TRY</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($tier->commission_type === 'fixed')
                                                <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold">{{ __('messages.fixed_amount') }}</span>
                                            @else
                                                <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-lg text-xs font-bold">{{ __('messages.percentage_type') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-black text-slate-800">
                                                {{ number_format($tier->commission_value, 2) }}
                                                {{ $tier->commission_type === 'fixed' ? 'TRY' : '%' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <button wire:click="deleteTier({{ $tier->id }})" wire:confirm="{{ __('messages.confirm_delete_tier') }}" class="text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 p-2 rounded-lg transition" title="{{ __('messages.delete_btn') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-500 text-sm font-bold">{{ __('messages.no_tiers_added_desc') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- REJECT REQUEST MODAL -->
    <div x-show="rejectModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-gray-800">{{ __('messages.reject_reason_title') }}</h3>
            <textarea x-model="rejectNotes" class="w-full border-gray-300 rounded-lg text-sm" placeholder="{{ __('messages.write_reject_reason_placeholder') }}" rows="3"></textarea>
            <div class="flex justify-end space-x-2 space-x-reverse">
                <button x-on:click="rejectModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold">{{ __('messages.cancel_btn') }}</button>
                <button x-on:click="
                    $wire.rejectRequest(rejectId, rejectNotes);
                    rejectModal = false;
                    rejectNotes = '';
                " wire:loading.attr="disabled" wire:target="rejectRequest" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold flex items-center justify-center disabled:opacity-75 disabled:cursor-wait">
                    <span wire:loading.remove wire:target="rejectRequest">{{ __('messages.confirm_reject_btn') }}</span>
                    <span wire:loading wire:target="rejectRequest" class="flex items-center">
                        <svg class="animate-spin ml-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        يُحمل...
                    </span>
                </button>
            </div>
        </div>
    </div>


        </div>
    </main>
</div>
