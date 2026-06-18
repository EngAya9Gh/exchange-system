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
                <option value="all">{{ __('messages.all_roles') }}</option>
                <option value="admin">{{ __('messages.role_admin_label') }}</option>
                <option value="agent">{{ __('messages.role_agent_label') }}</option>
                <option value="customer">{{ __('messages.role_customer_label') }}</option>
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

    <!-- Users Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-sm text-start text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-white/50 backdrop-blur-sm border-b border-white/40">
                <tr>
                    <th class="px-6 py-3">{{ __('messages.name_label') }}</th>
                    <th class="px-6 py-3">{{ __('messages.email') }}</th>
                    <th class="px-6 py-3">{{ __('messages.phone') }}</th>
                    <th class="px-6 py-3">{{ __('messages.role') }}</th>
                    <th class="px-6 py-3">{{ __('messages.status') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.actions_label') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="bg-white/30 border-b border-white/30 hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4" dir="ltr">{{ $user->phone ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($user->role === 'admin')
                                <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.role_admin') }}</span>
                            @elseif($user->role === 'agent')
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.role_agent') }}</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ __('messages.role_customer') }}</span>
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
                            <button wire:click="editUser({{ $user->id }})" class="text-primary-600 hover:text-primary-800">
                                {{ __('messages.edit') }}
                            </button>
                            |
                            <button wire:click="toggleStatus({{ $user->id }})" class="{{ $user->is_active ? 'text-danger-600 hover:text-danger-800' : 'text-success-600 hover:text-success-800' }}">
                                {{ $user->is_active ? __('messages.suspend') : __('messages.activate') }}
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
                        <div wire:key="field-email">
                            <x-input-label value="{{ __('messages.email') }}" />
                            <x-text-input wire:model="email" type="email" class="mt-1 block w-full" dir="ltr" autocomplete="off" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div wire:key="field-phone">
                            <x-input-label value="{{ __('messages.phone') }}" />
                            <x-text-input wire:model="phone" type="text" class="mt-1 block w-full" dir="ltr" placeholder="+201..." autocomplete="off" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div wire:key="field-password">
                            <x-input-label value="{{ __('messages.password') }}{{ $editingUserId ? __('messages.leave_blank_to_keep') : '' }}" />
                            <x-password-input wire:model="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div wire:key="field-role">
                            <x-input-label value="{{ __('messages.role') }}" />
                            <select wire:model="role" class="mt-1 block w-full bg-white/50 backdrop-blur-md border-white/60 focus:bg-white focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm py-2 transition-all duration-300">
                                <option value="customer">{{ __('messages.role_customer_label') }}</option>
                                <option value="agent">{{ __('messages.role_agent_label') }}</option>
                                <option value="admin">{{ __('messages.role_admin_label') }}</option>
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
</div>
