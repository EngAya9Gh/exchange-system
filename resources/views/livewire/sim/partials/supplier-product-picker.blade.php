{{--
    Pick a package's product from a supplier's list: fills operator / tip / code and shows the listed cost and profit.
    Expects $supplier, $pickModel (Livewire property), $groups (products grouped by group), $tip, $code, $price.
--}}
@php
    $picked = \App\Models\SimPackage::findSupplierProduct($supplier, $tip, $code);
    $priceValue = is_numeric($price) ? (float) $price : null;
    $margin = $picked && $priceValue !== null ? $priceValue - (float) $picked['cost'] : null;
@endphp
<select wire:model.live="{{ $pickModel }}" class="{{ $inputClass }} mb-2" dir="ltr">
    <option value="">— {{ __('messages.sim_pick_product', ['supplier' => $supplier]) }} —</option>
    @foreach($groups as $group => $products)
        <optgroup label="{{ $group }}">
            @foreach($products as $product)
                <option value="{{ $product['tip'] }}|{{ $product['code'] }}">{{ $product['name'] }} · {{ $product['code'] }} · {{ \App\Models\SimPackage::formatPrice($product['cost']) }} ₺</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
@if(!filled($code) && !empty($suggestion))
    <p class="text-[11px] font-bold text-emerald-800 mb-1.5">
        {{ __('messages.sim_pick_suggested') }}: <span dir="ltr">{{ $suggestion['name'] }} · {{ $suggestion['code'] }}</span>
        <button type="button" wire:click="$set('{{ $pickModel }}', '{{ $suggestion['tip'] }}|{{ $suggestion['code'] }}')" class="ms-1 underline text-primary-600">{{ __('messages.sim_pick_use') }}</button>
    </p>
@endif
@if($picked && $margin !== null)
    <p class="text-[11px] font-bold mt-1.5 {{ $margin < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
        {{ __('messages.sim_api_cost') }}: <span dir="ltr">{{ \App\Models\SimPackage::formatPrice($picked['cost']) }} ₺</span> ·
        @if($margin < 0)
            ⚠️ {{ __('messages.sim_pick_loss', ['amount' => \App\Models\SimPackage::formatPrice(abs($margin))]) }}
        @else
            {{ __('messages.sim_api_profit') }}: <span dir="ltr">{{ \App\Models\SimPackage::formatPrice($margin) }} ₺</span>
        @endif
    </p>
@elseif(filled($code) && !$picked)
    <p class="text-[11px] font-bold text-slate-500 mt-1.5">{{ __('messages.sim_pick_not_listed') }}</p>
@endif
