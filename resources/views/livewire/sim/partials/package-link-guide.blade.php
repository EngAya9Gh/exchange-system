{{--
    «📘 دليل ربط الباقة بالموردين» in the packages form: where each value comes from, with examples read from
    config/sim_supplier_products.php (a product missing from the list is simply not shown).
    Optional: $open (bool) — expanded on load.
--}}
@php
    $guideExamples = [
        // [supplier, tip, code, our package type]
        ['kontor86', 'TeleCard', '26240', 'for_physical'],
        ['kontor86', 'TeleCard', '2285493', 'for_fast'],
        ['kontor86', 'TeleCard', '35250', 'for_esim'],
        ['telekomtl', 'eSIM', '115', 'for_physical'],
        ['telekomtl', 'OnlineKart', '1111', 'for_esim'],
        ['telekomtl', 'TeleCard', '1233', 'for_europe'],
    ];
@endphp
<details class="group rounded-2xl border border-sky-200 bg-sky-50/60" @if($open ?? false) open @endif>
    <summary class="cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden flex items-center justify-between gap-3 px-4 py-3">
        <span class="text-sm font-black text-sky-900">📘 {{ __('sim_guide.title') }}</span>
        <span class="shrink-0 text-[11px] font-black text-sky-700 bg-white border border-sky-200 rounded-lg px-2.5 py-1">
            <span class="group-open:hidden">{{ __('sim_guide.show') }}</span>
            <span class="hidden group-open:inline">{{ __('sim_guide.hide') }}</span>
        </span>
    </summary>

    <div class="px-4 pb-4 space-y-4 text-xs text-slate-700 leading-relaxed">
        <p class="font-bold text-sky-900">{{ __('sim_guide.intro') }}</p>

        <div>
            <div class="font-black text-slate-800 mb-1">{{ __('sim_guide.where_title') }}</div>
            <ul class="space-y-1 list-disc ps-5">
                <li>{{ __('sim_guide.where_1') }}</li>
                <li>{{ __('sim_guide.where_2') }}</li>
                <li>{{ __('sim_guide.where_3') }}</li>
            </ul>
        </div>

        <div>
            <div class="font-black text-slate-800 mb-1">{{ __('sim_guide.panel_title') }}</div>
            <ul class="space-y-1 list-disc ps-5">
                <li>{{ __('sim_guide.panel_1') }}</li>
                <li>{{ __('sim_guide.panel_2') }}</li>
                <li>{{ __('sim_guide.panel_3') }}</li>
                <li>{{ __('sim_guide.panel_5') }}</li>
                <li class="font-bold text-amber-800">{{ __('sim_guide.panel_4') }}</li>
            </ul>
        </div>

        <div>
            <div class="font-black text-slate-800 mb-1.5">{{ __('sim_guide.examples_title') }}</div>
            <div class="overflow-x-auto rounded-xl border border-sky-100 bg-white">
                <table class="min-w-full text-[11px]">
                    <thead class="bg-slate-50 text-slate-500 font-bold">
                        <tr>
                            <th class="px-3 py-2 text-start whitespace-nowrap">{{ __('sim_guide.col_for') }}</th>
                            <th class="px-3 py-2 text-start whitespace-nowrap">{{ __('sim_guide.col_supplier') }}</th>
                            <th class="px-3 py-2 text-start whitespace-nowrap">{{ __('sim_guide.col_operator') }}</th>
                            <th class="px-3 py-2 text-start whitespace-nowrap">{{ __('sim_guide.col_tip') }}</th>
                            <th class="px-3 py-2 text-start whitespace-nowrap">{{ __('sim_guide.col_code') }}</th>
                            <th class="px-3 py-2 text-start whitespace-nowrap">{{ __('sim_guide.col_cost') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('sim_guide.col_product') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($guideExamples as [$exSupplier, $exTip, $exCode, $exFor])
                            @php $exProduct = \App\Models\SimPackage::findSupplierProduct($exSupplier, $exTip, $exCode); @endphp
                            @continue(!$exProduct)
                            <tr>
                                <td class="px-3 py-2 font-bold whitespace-nowrap">{{ __('sim_guide.' . $exFor) }}</td>
                                <td class="px-3 py-2 font-black whitespace-nowrap" dir="ltr">{{ $exSupplier }}</td>
                                <td class="px-3 py-2 font-mono font-bold text-indigo-700" dir="ltr">{{ $exProduct['operator'] }}</td>
                                <td class="px-3 py-2 font-mono font-bold text-indigo-700" dir="ltr">{{ $exProduct['tip'] }}</td>
                                <td class="px-3 py-2 font-mono font-black text-indigo-700" dir="ltr">{{ $exProduct['code'] }}</td>
                                <td class="px-3 py-2 whitespace-nowrap" dir="ltr">{{ \App\Models\SimPackage::formatPrice($exProduct['cost']) }} ₺</td>
                                <td class="px-3 py-2 min-w-[150px] text-slate-500" dir="ltr">{{ $exProduct['name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="font-black text-slate-800 mb-1">{{ __('sim_guide.mistakes_title') }}</div>
            <ul class="space-y-1 list-disc ps-5">
                @foreach(range(1, 6) as $i)
                    <li @class(['font-bold text-rose-700' => $i === 1])>{{ __('sim_guide.mistake_' . $i) }}</li>
                @endforeach
                <li>{{ __('sim_guide.preferred') }}</li>
            </ul>
        </div>
    </div>
</details>
