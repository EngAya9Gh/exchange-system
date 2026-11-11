{{--
    Package card on «اختر الباقة».
    Expects: $package (SimPackage), $isSelected (bool), $instant (bool: loads automatically through kontor86).
    Artwork is inline SVG (flag motif + SIM card), so there are no image files to host.
--}}
@php
    $isEurope = $package->region === \App\Models\SimPackage::REGION_EUROPE;

    $theme = $isEurope ? [
        'header' => 'from-blue-600 via-indigo-700 to-blue-950',
        'selected' => 'border-blue-500 ring-4 ring-blue-100 shadow-soft-xl -translate-y-1',
        'cta' => 'bg-blue-50 text-blue-700 group-hover:bg-blue-600 group-hover:text-white',
        'ctaOn' => 'bg-blue-600 text-white shadow-md shadow-blue-600/30',
    ] : [
        'header' => 'from-red-500 via-red-600 to-rose-800',
        'selected' => 'border-primary-500 ring-4 ring-primary-100 shadow-soft-xl -translate-y-1',
        'cta' => 'bg-primary-50 text-primary-700 group-hover:bg-primary-600 group-hover:text-white',
        'ctaOn' => 'bg-primary-600 text-white shadow-md shadow-primary-600/30',
    ];

    // 5-point star polygon (point up by default)
    $starPoints = function (float $cx, float $cy, float $r, float $rotation = -90): string {
        $points = [];
        for ($i = 0; $i < 10; $i++) {
            $angle = deg2rad($rotation + $i * 36);
            $length = $i % 2 === 0 ? $r : $r * 0.4;
            $points[] = round($cx + $length * cos($angle), 2) . ',' . round($cy + $length * sin($angle), 2);
        }

        return implode(' ', $points);
    };

    $pricePerGb = $package->data_gb > 0 ? (float) $package->price / $package->data_gb : null;
@endphp

<button type="button" wire:key="sim-pkg-{{ $package->id }}" wire:click="selectPackage({{ $package->id }})"
    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
    class="group relative text-start rounded-3xl bg-white border-2 overflow-hidden flex flex-col transition-all duration-300 focus:outline-none focus-visible:ring-4 focus-visible:ring-slate-200 {{ $isSelected ? $theme['selected'] : 'border-slate-100 shadow-soft hover:-translate-y-1 hover:shadow-soft-xl hover:border-slate-200' }}">

    {{-- Artwork header --}}
    <div class="relative h-40 w-full bg-gradient-to-br {{ $theme['header'] }} overflow-hidden">
        <div class="absolute -top-12 -start-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-20 end-0 w-48 h-48 rounded-full {{ $isEurope ? 'bg-amber-300/20' : 'bg-orange-300/25' }} blur-3xl"></div>

        {{-- Flag motif --}}
        @if($isEurope)
            <svg class="absolute -start-12 top-1/2 -translate-y-1/2 w-48 h-48 text-yellow-300/60" viewBox="0 0 120 120" fill="currentColor" aria-hidden="true">
                @for($i = 0; $i < 12; $i++)
                    <polygon points="{{ $starPoints(60 + 40 * cos(deg2rad($i * 30)), 60 + 40 * sin(deg2rad($i * 30)), 7) }}" />
                @endfor
            </svg>
        @else
            <svg class="absolute -start-8 top-1/2 -translate-y-1/2 w-52 h-44 text-white/[0.13]" viewBox="0 0 120 100" fill="currentColor" aria-hidden="true">
                <path d="M64.25 32.34A30 30 0 1 0 64.25 67.66A24 24 0 1 1 64.25 32.34Z" />
                <polygon points="{{ $starPoints(78, 50, 11, 180) }}" />
            </svg>
        @endif

        {{-- SIM card --}}
        <div class="absolute end-5 bottom-4 -rotate-12 transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-105">
            <svg class="w-14 h-[72px] drop-shadow-[0_10px_14px_rgba(0,0,0,0.35)]" viewBox="0 0 60 78" aria-hidden="true">
                <path d="M8 1H40L59 20V69A8 8 0 0 1 51 77H8A8 8 0 0 1 0 69V9A8 8 0 0 1 8 1Z" fill="#ffffff" />
                <rect x="9" y="9" width="18" height="3" rx="1.5" fill="#e2e8f0" />
                @if($package->isEsim())
                    {{-- eSIM: QR-style mark instead of a chip --}}
                    <g fill="#1e293b">
                        <rect x="12" y="28" width="11" height="11" rx="2" /><rect x="36" y="28" width="11" height="11" rx="2" /><rect x="12" y="51" width="11" height="11" rx="2" />
                        <rect x="27" y="30" width="5" height="5" rx="1" /><rect x="27" y="42" width="5" height="5" rx="1" /><rect x="36" y="45" width="5" height="5" rx="1" />
                        <rect x="42" y="53" width="5" height="5" rx="1" /><rect x="27" y="55" width="5" height="5" rx="1" />
                    </g>
                @else
                    <rect x="12" y="28" width="35" height="30" rx="5" fill="#fcd34d" />
                    <path d="M12 38H23M36 38H47M12 48H23M36 48H47M23 28V58M36 28V58" stroke="#b45309" stroke-opacity=".55" stroke-width="1.6" fill="none" />
                @endif
            </svg>
        </div>

        {{-- Network & label --}}
        <div class="absolute top-3 inset-x-3 flex items-start justify-between gap-2">
            @if($package->network_name)
                <span class="inline-flex items-center gap-1 rounded-full bg-white/15 backdrop-blur-sm ring-1 ring-white/25 text-white text-[11px] font-black px-2.5 py-1" title="{{ __('messages.sim_network') }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                    <span dir="ltr">{{ $package->network_name }}</span>
                </span>
            @else
                <span></span>
            @endif
            @include('livewire.sim.partials.package-badge', ['badge' => $package->badge])
        </div>

        {{-- Data & duration --}}
        <div class="absolute bottom-4 start-4 text-white">
            <div class="text-5xl font-black leading-none tracking-tight drop-shadow-sm"><span dir="ltr">{{ $package->data_gb }}<span class="text-xl font-extrabold ms-1 opacity-90">GB</span></span></div>
            <div class="mt-2 inline-flex items-center gap-1.5 text-sm font-bold text-white/90">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                {{ $package->durationLabel() }}
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="p-5 flex flex-col flex-1 w-full">
        <div class="flex flex-wrap items-center gap-1.5 mb-3">
            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $package->isEsim() ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-100' }}">
                {{ $package->isEsim() ? __('messages.sim_type_esim') : __('messages.sim_type_physical') }}
            </span>
            @if($instant)
                <span class="inline-flex items-center gap-1 text-[11px] font-black px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2L4.09 12.6a.5.5 0 00.38.82H11l-1 8.58L18.91 11.4a.5.5 0 00-.38-.82H13l1-8.58z"/></svg>
                    {{ __('messages.sim_instant_load') }}
                </span>
            @endif
        </div>

        <div class="text-[15px] font-black text-slate-800 leading-snug">{{ $package->localized_name }}</div>
        @if($package->localized_description)
            <p class="text-xs text-slate-500 leading-relaxed mt-1 line-clamp-2">{{ $package->localized_description }}</p>
        @endif

        <div class="mt-auto pt-4">
            <div class="pt-4 border-t border-dashed border-slate-200 flex items-end justify-between gap-3">
                <div>
                    <div class="text-[11px] font-bold text-slate-400">{{ __('messages.sim_price') }}</div>
                    <div class="text-2xl font-black text-slate-900 leading-tight"><span dir="ltr">{{ \App\Models\SimPackage::formatPrice($package->price) }} <span class="text-base font-extrabold text-slate-500">₺</span></span></div>
                    @if($pricePerGb !== null && $package->data_gb > 1)
                        <div class="text-[11px] font-bold text-slate-400 mt-0.5">{{ __('messages.sim_price_per_gb', ['price' => \App\Models\SimPackage::formatPrice(round($pricePerGb))]) }}</div>
                    @endif
                </div>
                <span class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-black transition {{ $isSelected ? $theme['ctaOn'] : $theme['cta'] }}">
                    @if($isSelected)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        {{ __('messages.sim_selected') }}
                    @else
                        {{ __('messages.sim_choose') }}
                        <svg class="w-4 h-4 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    @endif
                </span>
            </div>
        </div>
    </div>
</button>
