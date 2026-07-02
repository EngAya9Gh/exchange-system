<div class="bg-white rounded-[24px] shadow-soft border border-slate-50 p-6 sm:p-8 mt-6">
    <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-2">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        {{ __('messages.new_deposit_request') }}
    </h3>

    @if (session()->has('success_message'))
        <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="font-bold">{{ session('success_message') }}</span>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-6">
        <!-- Payment Methods -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-4">{{ __('messages.choose_payment_method') }}</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($methods as $key => $details)
                    <div wire:click="$set('method', '{{ $key }}')" class="cursor-pointer border-2 rounded-xl p-4 transition-all flex flex-col items-center gap-3 {{ $method === $key ? 'border-primary-500 bg-primary-50 shadow-md transform scale-[1.02]' : 'border-gray-100 hover:border-gray-200 bg-gray-50' }}">
                        <img src="{{ asset($details['logo']) }}" alt="{{ $details['name'] }}" class="h-16 object-contain rounded-lg">
                        <span class="font-bold text-gray-800">{{ $details['name'] }}</span>
                        <div class="text-xs text-gray-500 text-center whitespace-pre-line">{{ $details['details'] }}</div>
                        @if($key === 'sham')
                            <img src="{{ asset('sham-qr.png') }}" alt="QR" class="w-24 mt-2 hidden" onerror="this.style.display='none'">
                        @endif
                        <input type="radio" wire:model="method" value="{{ $key }}" class="hidden">
                    </div>
                @endforeach
            </div>
            @error('method') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Amount -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('messages.transferred_amount') }}</label>
                <div class="relative" x-data="{
                    formatted: '',
                    formatNumber(val) {
                        if (!val && val !== 0 && val !== '0') return '';
                        let str = val.toString().replace(/[^0-9.]/g, '');
                        let parts = str.split('.');
                        let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        return parts.length > 1 ? intPart + '.' + parts[1] : intPart;
                    }
                }" x-init="
                    formatted = formatNumber($wire.amount);
                    $watch('$wire.amount', val => { if(document.activeElement !== $refs.input) formatted = formatNumber(val); });
                ">
                    <input x-ref="hidden" type="hidden" wire:model="amount">
                    <input x-ref="input" type="text" inputmode="decimal" x-model="formatted"
                        @input="
                            let val = $event.target.value.replace(/[^0-9.]/g, '');
                            let parts = val.split('.');
                            if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
                            val = parts.join('.');
                            $refs.hidden.value = val;
                            $refs.hidden.dispatchEvent(new Event('input', { bubbles: true }));
                            let displayInt = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                            formatted = parts.length > 1 ? displayInt + '.' + parts[1] : displayInt;
                        "
                        class="block w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 pl-12 shadow-sm font-bold text-lg" placeholder="{{ __('messages.amount_example') }}">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 font-bold">TRY</span>
                    </div>
                </div>
                @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            
            <div class="flex items-center text-sm text-gray-500 font-bold bg-gray-50 rounded-xl p-4 border border-gray-100">
                <svg class="w-8 h-8 text-green-500 ml-3 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                {{ __('messages.redirect_whatsapp_note') }}
            </div>
        </div>

        <!-- Submit -->
        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-8 rounded-xl shadow-md transition-transform hover:-translate-y-1 focus:ring-4 focus:ring-primary-300 flex items-center gap-2">
                <span wire:loading.remove wire:target="submit">{{ __('messages.confirm_and_continue_whatsapp') }}</span>
                <span wire:loading wire:target="submit">{{ __('messages.sending') }}</span>
            </button>
        </div>
    </form>
</div>
