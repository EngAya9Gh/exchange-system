<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 font-cairo">{{ __('messages.user_management') }}</h2>
        <x-primary-button wire:click="createNewUser" class="bg-primary-600 hover:bg-primary-700 w-full sm:w-auto">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            {{ __('messages.add_user') }}
        </x-primary-button>
    </div>

    <!-- Filters -->
    <x-card class="mb-6 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <x-text-input wire:model.live="searchQuery" placeholder="{{ __('messages.search_users_placeholder') }}" class="w-full sm:w-1/2" />
            <select wire:model.live="roleFilter" class="bg-white/50 backdrop-blur-md border-white/60 focus:bg-white focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm w-full sm:w-1/4 py-2 transition-all duration-300">
                <option value="all">{{ __('messages.all_roles') ?? 'كل الأدوار' }}</option>
                @foreach($availableRoles as $r)
                    <option value="{{ $r->name }}">{{ $r->name === 'Super Admin' ? 'مدير النظام' : ($r->name === 'Agent' ? 'موظف فرع' : ($r->name === 'Customer' ? 'عميل عادي' : $r->name)) }}</option>
                @endforeach
            </select>
        </div>
    </x-card>

    <!-- Session Messages -->
    @if(session('success'))
        <div class="mb-4 bg-success-50 text-success-700 p-4 rounded-lg border border-success-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-danger-50 text-danger-700 p-4 rounded-lg border border-danger-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Special Vodafone Cash rates set on another general rate: they stay fixed, so they are listed for review --}}
    @if($customRatesToReview->isNotEmpty())
        <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-black text-amber-900">⚠️ {{ __('messages.custom_rate_review_title', ['count' => $customRatesToReview->count()]) }}</div>
                    <p class="text-xs text-amber-800 mt-1">{{ __('messages.custom_rate_review_desc', ['rate' => \App\Livewire\Admin\UserManagement::formatRate($generalVodafoneRate)]) }}</p>
                </div>
                <button type="button" wire:click="keepAllCustomRates" wire:loading.attr="disabled"
                    class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold bg-white border border-amber-300 text-amber-900 hover:bg-amber-100 transition">
                    {{ __('messages.custom_rate_keep_all') }}
                </button>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-xs text-start">
                    <thead class="text-amber-900/70">
                        <tr>
                            <th class="py-1.5 pe-4 text-start font-bold">{{ __('messages.custom_rate_col_customer') }}</th>
                            <th class="py-1.5 pe-4 text-start font-bold">{{ __('messages.custom_rate_col_special') }}</th>
                            <th class="py-1.5 pe-4 text-start font-bold">{{ __('messages.custom_rate_col_base') }}</th>
                            <th class="py-1.5 pe-4 text-start font-bold">{{ __('messages.custom_rate_col_current') }}</th>
                            <th class="py-1.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-200/70">
                        @foreach($customRatesToReview as $reviewUser)
                            @php $isAbove = (float) $reviewUser->custom_vodafone_rate > $generalVodafoneRate; @endphp
                            <tr wire:key="custom-rate-review-{{ $reviewUser->id }}">
                                <td class="py-2 pe-4 font-bold text-slate-800">{{ $reviewUser->name }}</td>
                                <td class="py-2 pe-4 font-black text-slate-900">
                                    <span dir="ltr">{{ \App\Livewire\Admin\UserManagement::formatRate((float) $reviewUser->custom_vodafone_rate) }}</span>
                                    @if($isAbove)
                                        <span class="ms-1 rounded-full bg-rose-100 text-rose-700 px-2 py-0.5 text-[10px] font-bold">{{ __('messages.custom_rate_above_general') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 pe-4 text-slate-600" dir="ltr">{{ $reviewUser->custom_vodafone_rate_base !== null ? \App\Livewire\Admin\UserManagement::formatRate((float) $reviewUser->custom_vodafone_rate_base) : '—' }}</td>
                                <td class="py-2 pe-4 font-bold text-slate-800" dir="ltr">{{ \App\Livewire\Admin\UserManagement::formatRate($generalVodafoneRate) }}</td>
                                <td class="py-2 text-end whitespace-nowrap">
                                    <button type="button" wire:click="editUser({{ $reviewUser->id }})" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.edit') }}</button>
                                    <button type="button" wire:click="keepCustomRate({{ $reviewUser->id }})" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white border border-amber-300 text-amber-900 hover:bg-amber-100 transition">{{ __('messages.custom_rate_keep') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Users Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-sm text-start text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-white/50 backdrop-blur-sm border-b border-white/40">
                <tr>
                    <th class="px-6 py-3">{{ __('messages.name_label') }}</th>
                    <th class="px-6 py-3">اسم المستخدم</th>
                    <th class="px-6 py-3">{{ __('messages.phone') }}</th>
                    <th class="px-6 py-3">الرصيد المتاح</th>
                    <th class="px-6 py-3">{{ __('messages.role') }}</th>
                    <th class="px-6 py-3">{{ __('messages.status') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.actions_label') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="bg-white/30 border-b border-white/30 hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900">
                            {{ $user->name }}
                            @if($user->api_enabled && $user->hasApiToken())
                                <span class="inline-flex items-center rounded-full bg-slate-800 text-white px-2 py-0.5 text-[10px] font-black align-middle" title="{{ __('messages.api_section_title') }}">🔌 API</span>
                            @endif
                            @if($user->hasCustomVodafoneRate())
                                @php $needsReview = $user->customVodafoneRateNeedsReview($generalVodafoneRate); @endphp
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $needsReview ? 'bg-amber-100 text-amber-800' : 'bg-indigo-50 text-indigo-700' }}"
                                        title="{{ __('messages.custom_rate_label') }}">
                                        @if($needsReview)<span>⚠️</span>@endif
                                        <span>{{ __('messages.custom_rate_badge') }}</span>
                                        <span dir="ltr">{{ \App\Livewire\Admin\UserManagement::formatRate((float) $user->custom_vodafone_rate) }}</span>
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4" dir="ltr">{{ $user->username }}</td>
                        <td class="px-6 py-4" dir="ltr">{{ $user->phone ?? '-' }}</td>
                        <td class="px-6 py-4 font-bold text-center">
                            <div class="flex flex-col items-center gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="{{ $user->balance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ number_format(abs($user->balance), 2) }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $user->balance >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $user->balance >= 0 ? 'دائن (له)' : 'مدين (عليه)' }}
                                    </span>
                                </div>
                                <div class="text-xs font-normal {{ $user->has_unlimited_balance ? 'text-gray-500' : 'text-slate-500' }}">
                                    @if($user->has_unlimited_balance)
                                        (سقف مفتوح)
                                    @else
                                        الحد الأقصى: {{ number_format($user->balance_limit, 2) }}
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->hasRole('Super Admin'))
                                <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.role_admin') ?? 'مدير النظام' }}</span>
                            @elseif($user->hasRole('Agent'))
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.role_agent') ?? 'موظف فرع' }}</span>
                            @elseif($user->hasRole('Customer'))
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.role_customer') ?? 'عميل عادي' }}</span>
                            @else
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $user->roles->first()?->name ?? 'بدون دور' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <span class="bg-success-100 text-success-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.status_active') }}</span>
                            @else
                                <span class="bg-danger-100 text-danger-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.status_suspended') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button wire:click="openStatementModal({{ $user->id }})" class="text-slate-600 hover:text-slate-800 font-bold" title="كشف الحساب">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </button>
                            |
                            <button wire:click="editUser({{ $user->id }})" class="text-primary-600 hover:text-primary-800">
                                {{ __('messages.edit') }}
                            </button>
                            |
                            <button wire:click="toggleStatus({{ $user->id }})" class="{{ $user->is_active ? 'text-danger-600 hover:text-danger-800' : 'text-success-600 hover:text-success-800' }}">
                                {{ $user->is_active ? __('messages.suspend') : __('messages.activate') }}
                            </button>
                            |
                            <button onclick="confirmDeletion({{ $user->id }})" class="text-red-600 hover:text-red-800 font-bold">
                                {{ __('messages.delete_btn') ?? 'حذف' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                            {{ __('messages.no_users_found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $users->links() }}
        </div>
    </x-card>

    <!-- Create / Edit Modal -->
    @if($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" wire:key="user-form-modal">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                        {{ $editingUserId ? __('messages.edit_user') : __('messages.add_new_user') }}
                    </h3>
                    <div class="space-y-4">
                        <div wire:key="field-name">
                            <x-input-label value="{{ __('messages.name_label') }}" />
                            <x-text-input wire:model="name" type="text" class="mt-1 block w-full" autocomplete="off" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div wire:key="field-username">
                            <x-input-label value="اسم المستخدم" />
                            <x-text-input wire:model="username" type="text" class="mt-1 block w-full text-start" autocomplete="off" />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>
                        <div wire:key="field-phone">
                            <x-input-label value="{{ __('messages.phone') }}" />
                            <x-text-input wire:model="phone" type="text" class="mt-1 block w-full text-start" placeholder="+201..." autocomplete="off" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div wire:key="field-balance">
                            <x-input-label value="الرصيد المتاح حالياً" />
                            <x-text-input wire:model="balance" type="number" step="0.01" class="mt-1 block w-full text-start font-bold text-green-600" autocomplete="off" />
                            <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                        </div>
                        
                        <div wire:key="field-has-limit" class="mt-4">
                            <div class="flex items-center gap-2">
                                <input wire:model.live="has_unlimited_balance" id="has_unlimited_balance" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                <label for="has_unlimited_balance" class="text-sm text-gray-800 font-bold">الحساب بسقف مفتوح (رصيد غير محدود)</label>
                            </div>
                        </div>

                        @if(!$has_unlimited_balance)
                            <div wire:key="field-balance-limit" class="mt-2 bg-red-50 p-3 rounded-lg border border-red-100">
                                <x-input-label value="الحد الائتماني (أقصى مديونية أو سقف للرصيد)" />
                                <x-text-input wire:model="balance_limit" type="number" step="0.01" min="0" class="mt-1 block w-full text-start font-bold text-red-600" autocomplete="off" />
                                <x-input-error :messages="$errors->get('balance_limit')" class="mt-2" />
                            </div>
                        @endif
                        {{-- Special Vodafone Cash rate: same account type and screens, only the rate changes --}}
                        <div wire:key="field-custom-rate" class="mt-2 bg-indigo-50/60 p-3 rounded-lg border border-indigo-100">
                            <x-input-label for="custom_vodafone_rate" value="{{ __('messages.custom_rate_label') }} (TRY → EGP)" />
                            <x-text-input id="custom_vodafone_rate" wire:model="custom_vodafone_rate" type="text" inputmode="decimal" dir="ltr"
                                placeholder="{{ \App\Livewire\Admin\UserManagement::formatRate($generalVodafoneRate) }}"
                                class="mt-1 block w-full text-start font-bold text-indigo-700" autocomplete="off" />
                            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">{{ __('messages.custom_rate_hint', ['rate' => \App\Livewire\Admin\UserManagement::formatRate($generalVodafoneRate)]) }}</p>
                            @if($editingUser && $editingUser->customVodafoneRateNeedsReview($generalVodafoneRate) && $editingUser->custom_vodafone_rate_base !== null)
                                <p class="text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-2 py-1 mt-1.5">
                                    ⚠️ {{ __('messages.custom_rate_changed_note', [
                                        'base' => \App\Livewire\Admin\UserManagement::formatRate((float) $editingUser->custom_vodafone_rate_base),
                                        'current' => \App\Livewire\Admin\UserManagement::formatRate($generalVodafoneRate),
                                    ]) }}
                                </p>
                            @endif
                            <x-input-error :messages="$errors->get('custom_vodafone_rate')" class="mt-2" />
                        </div>

                        {{-- Partner API: other systems order SIM packages with this account's balance --}}
                        @if($editingUser)
                            <div wire:key="field-partner-api" class="mt-2 bg-slate-50 p-3 rounded-lg border border-slate-200">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-sm font-black text-slate-800">🔌 {{ __('messages.api_section_title') }}</div>
                                    <a href="{{ route('api.docs') }}" target="_blank" rel="noopener" class="text-[11px] font-bold text-primary-600 hover:underline">{{ __('messages.api_docs_link') }}</a>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">{{ __('messages.api_section_hint') }}</p>

                                <label class="flex items-center gap-2 mt-3">
                                    <input wire:model="api_enabled" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="text-sm font-bold text-slate-700">{{ __('messages.api_enabled_label') }}</span>
                                </label>

                                @if($newApiToken)
                                    <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3" x-data="{ copied: false }">
                                        <div class="text-[11px] font-black text-emerald-900">{{ __('messages.api_key_new_title') }}</div>
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <code class="flex-1 min-w-0 break-all rounded bg-white px-2 py-1.5 text-xs font-mono text-slate-800 border border-emerald-100" dir="ltr">{{ $newApiToken }}</code>
                                            <button type="button" @click="navigator.clipboard.writeText(@js($newApiToken)); copied = true; setTimeout(() => copied = false, 1500)"
                                                class="shrink-0 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700">
                                                <span x-show="!copied">{{ __('messages.sim_copy') }}</span><span x-show="copied" x-cloak>✓</span>
                                            </button>
                                        </div>
                                        <p class="text-[11px] font-bold text-emerald-800 mt-1.5">{{ __('messages.api_key_shown_once') }}</p>
                                    </div>
                                @elseif($editingUser->hasApiToken())
                                    <div class="mt-3 text-xs text-slate-600">
                                        {{ __('messages.api_key_current') }} <code class="font-mono font-bold" dir="ltr">vc_…{{ $editingUser->api_token_hint }}</code>
                                        · {{ __('messages.api_last_used') }}: <span dir="ltr">{{ $editingUser->api_last_used_at?->format('Y-m-d H:i') ?? __('messages.api_never_used') }}</span>
                                    </div>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" wire:click="generateApiKey"
                                        @if($editingUser->hasApiToken()) wire:confirm="{{ __('messages.api_key_replace_confirm') }}" @endif
                                        class="px-3 py-1.5 rounded-lg text-xs font-black bg-slate-800 text-white hover:bg-slate-900 transition">
                                        {{ $editingUser->hasApiToken() ? __('messages.api_key_regenerate') : __('messages.api_key_generate') }}
                                    </button>
                                    @if($editingUser->hasApiToken())
                                        <button type="button" wire:click="revokeApiKey" wire:confirm="{{ __('messages.api_key_revoke_confirm') }}"
                                            class="px-3 py-1.5 rounded-lg text-xs font-black bg-white border border-rose-200 text-rose-700 hover:bg-rose-50 transition">
                                            {{ __('messages.api_key_revoke') }}
                                        </button>
                                    @endif
                                </div>

                                <div class="mt-3">
                                    <x-input-label for="api_allowed_ips" value="{{ __('messages.api_allowed_ips_label') }}" />
                                    <textarea id="api_allowed_ips" wire:model="api_allowed_ips" rows="2" dir="ltr" placeholder="203.0.113.10"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm font-mono shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                    <p class="text-[11px] text-slate-500 mt-1">{{ __('messages.api_allowed_ips_hint') }}</p>
                                    <x-input-error :messages="$errors->get('api_allowed_ips')" class="mt-2" />
                                </div>
                            </div>
                        @endif

                        <div wire:key="field-password">
                            <x-input-label value="{{ __('messages.password') }}{{ $editingUserId ? __('messages.leave_blank_to_keep') : '' }}" />
                            <x-password-input wire:model="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div wire:key="field-role">
                            <x-input-label value="{{ __('messages.role') }}" />
                            <select wire:model="role" class="mt-1 block w-full bg-white/50 backdrop-blur-md border-white/60 focus:bg-white focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm py-2 transition-all duration-300">
                                @foreach($availableRoles as $r)
                                    <option value="{{ $r->name }}">{{ $r->name === 'Super Admin' ? 'مدير النظام' : ($r->name === 'Agent' ? 'موظف فرع' : ($r->name === 'Customer' ? 'عميل عادي' : $r->name)) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                        <div class="flex items-center gap-2 mt-4" wire:key="field-active">
                            <input wire:model="is_active" id="is_active" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                            <label for="is_active" class="text-sm text-gray-600">حساب {{ __('messages.status_active') }}</label>
                        </div>
                        <div class="flex items-center gap-2 mt-2" wire:key="field-2fa">
                            <input wire:model="two_factor_enabled" id="two_factor_enabled" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                            <label for="two_factor_enabled" class="text-sm text-gray-600">{{ __('messages.enable_2fa') }}</label>
                        </div>
                        <div class="flex items-center gap-2 mt-2" wire:key="field-telegram-alerts">
                            <input wire:model="receives_telegram_alerts" id="receives_telegram_alerts" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                            <label for="receives_telegram_alerts" class="text-sm text-gray-600 font-bold">استلام إشعارات التلغرام للحوالات الجديدة (للمدراء)</label>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse gap-2">
                    <x-primary-button wire:click="saveUser" class="bg-primary-600 hover:bg-primary-700">
                        {{ __('messages.save') }}
                    </x-primary-button>
                    <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('messages.cancel_btn') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Statement Modal -->
    @if($showStatementModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity" wire:click="closeStatementModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[24px] text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-3 text-center sm:mt-0 sm:text-start w-full">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-xl leading-6 font-black text-gray-900 mb-2" id="modal-title">كشف حساب</h3>
                                    <p class="text-sm text-gray-500">{{ __('messages.customer') }}: <span class="font-bold text-gray-900">{{ $statementUserName }}</span> | {{ __('messages.current_balance') }}: 
                                        <span class="font-bold {{ $statementUserBalance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ number_format(abs($statementUserBalance), 2) }}
                                            <span class="text-xs {{ $statementUserBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                {{ $statementUserBalance >= 0 ? '(دائن)' : '(مدين)' }}
                                            </span>
                                        </span>
                                    </p>
                                </div>
                                <button wire:click="closeStatementModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto max-h-[60vh] border border-gray-100 rounded-xl">
                                <table class="w-full text-sm text-center text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 shadow-sm">
                                        <tr>
                                            <th class="px-4 py-3">التاريخ والوقت</th>
                                            <th class="px-4 py-3">نوع العملية</th>
                                            <th class="px-4 py-3">المبلغ</th>
                                            <th class="px-4 py-3">الرصيد بعد العملية</th>
                                            <th class="px-4 py-3">البيان (ملاحظات)</th>
                                            <th class="px-4 py-3">بواسطة</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($statementTransactions as $tx)
                                            <tr class="hover:bg-gray-50/50 transition">
                                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-gray-600" dir="ltr">{{ $tx->created_at->format('Y-m-d h:i A') }}</td>
                                                <td class="px-4 py-3 font-bold">
                                                    @if($tx->type === 'deposit')
                                                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-1 rounded">إيداع</span>
                                                    @elseif($tx->type === 'withdrawal')
                                                        <span class="bg-rose-100 text-rose-800 text-xs px-2 py-1 rounded">سحب</span>
                                                    @elseif($tx->type === 'payment')
                                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">سداد دفعة</span>
                                                    @else
                                                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">تعديل رصيد</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 font-bold whitespace-nowrap" dir="ltr">
                                                    @if(in_array($tx->type, ['deposit', 'payment']) || ($tx->type === 'adjustment' && $tx->balance_after >= $tx->balance_before))
                                                        <span class="text-emerald-600">+ {{ number_format($tx->amount, 2) }}</span>
                                                    @else
                                                        <span class="text-rose-600">- {{ number_format($tx->amount, 2) }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 font-bold whitespace-nowrap" dir="ltr">
                                                    <span class="{{ $tx->balance_after >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        {{ number_format(abs($tx->balance_after), 2) }}
                                                        <span class="text-[10px] font-normal">
                                                            {{ $tx->balance_after >= 0 ? '(دائن)' : '(مدين)' }}
                                                        </span>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate" title="{{ $tx->notes }}">
                                                    {{ $tx->notes ?? '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-xs text-gray-400">
                                                    {{ $tx->admin?->name ?? 'نظام' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">لا توجد حركات مالية مسجلة لهذا المستخدم.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function confirmDeletion(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن يتم حذف المستخدم نهائياً، سيتم نقله إلى الأرشيف (حذف ناعم).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'نعم، قم بالحذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteUser', id);
            }
        })
    }
</script>
@endpush
