<div {{ $attributes->merge(['class' => 'bg-white/60 backdrop-blur-xl border border-white/50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:rounded-3xl overflow-hidden relative z-10']) }}>
    @if(isset($header))
        <div class="px-6 py-4 border-b border-white/40 bg-white/30">
            {{ $header }}
        </div>
    @endif
    
    <div class="p-6 text-gray-900 relative">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-6 py-4 border-t border-white/40 bg-white/30">
            {{ $footer }}
        </div>
    @endif
</div>
