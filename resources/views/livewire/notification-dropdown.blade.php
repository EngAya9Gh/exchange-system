<div class="relative" x-data="{ open: @entangle('isOpen') }" wire:poll.10s="loadNotifications">
    <button wire:click="toggleDropdown" @click.away="open = false" class="relative text-slate-400 hover:text-primary-600 transition p-2 focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        @if($unreadCount > 0)
            <span class="absolute top-1 right-2 w-2.5 h-2.5 rounded-full bg-rose-500 border-2 border-white animate-pulse"></span>
        @endif
    </button>

    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute left-0 mt-2 w-[420px] bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50 origin-top-left" x-cloak>
        
        <div class="bg-slate-50 px-4 py-3 flex justify-between items-center border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800">الإشعارات <span class="text-xs text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full ml-1">{{ $unreadCount }}</span></h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs font-bold text-primary-600 hover:text-primary-700">تحديد الكل كمقروء</button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="px-4 py-4 border-b border-slate-50 transition cursor-pointer flex gap-4 {{ $notification->read_at ? 'bg-white opacity-80 hover:bg-slate-50' : 'bg-primary-50/40 hover:bg-primary-50/60' }}" 
                     @if(!$notification->read_at) wire:click="markAsRead('{{ $notification->id }}')" @endif>
                    
                    <div class="shrink-0 w-12 h-12 rounded-full flex items-center justify-center {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-white shadow-sm text-primary-600' }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $notification->data['icon'] ?? 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }}"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0 text-right pt-1">
                        <p class="text-sm font-bold truncate {{ $notification->read_at ? 'text-slate-600' : 'text-slate-800' }}">{{ $notification->data['title'] ?? 'إشعار جديد' }}</p>
                        <p class="text-xs mt-1 leading-relaxed {{ $notification->read_at ? 'text-slate-400' : 'text-slate-600' }}">{{ $notification->data['message'] ?? '' }}</p>
                        <p class="text-[11px] font-semibold mt-2 {{ $notification->read_at ? 'text-slate-300' : 'text-primary-500' }}">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @if(!$notification->read_at)
                        <div class="w-2 h-2 rounded-full bg-primary-500 mt-2 shrink-0"></div>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <p class="text-sm font-bold text-slate-400">لا توجد إشعارات جديدة</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
