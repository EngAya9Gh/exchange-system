<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 font-cairo">{{ __('messages.roles_and_permissions') }}</h2>
        <x-primary-button wire:click="createNewRole" class="bg-primary-600 hover:bg-primary-700 w-full sm:w-auto">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            {{ __('messages.add_new_role') }}
        </x-primary-button>
    </div>

    <!-- Session Messages -->
    @if(session('success'))
        <div class="mb-4 bg-success-50 text-success-700 p-4 rounded-lg border border-success-200 font-bold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-danger-50 text-danger-700 p-4 rounded-lg border border-danger-200 font-bold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Roles Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-sm text-start text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-white/50 backdrop-blur-sm border-b border-white/40">
                <tr>
                    <th class="px-6 py-3">{{ __('messages.role_name') }}</th>
                    <th class="px-6 py-3">{{ __('messages.users_count') }}</th>
                    <th class="px-6 py-3">{{ __('messages.granted_permissions') }}</th>
                    <th class="px-6 py-3 text-center">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr class="bg-white/30 border-b border-white/30 hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900">
                            @if($role->name === 'Super Admin')
                                <span class="text-purple-700">{{ __('messages.super_admin_role') }}</span>
                            @elseif($role->name === 'Agent')
                                <span class="text-blue-700">{{ __('messages.agent_role') }}</span>
                            @elseif($role->name === 'Customer')
                                <span class="text-gray-700">{{ __('messages.customer_role') }}</span>
                            @else
                                {{ $role->name }}
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 text-slate-800 text-xs font-bold px-2.5 py-0.5 rounded-full">{{ $role->users_count }} {{ __('messages.user') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @if($role->name === 'Super Admin')
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">{{ __('messages.full_permissions') }}</span>
                                @else
                                    @forelse($role->permissions as $permission)
                                        <span class="bg-primary-50 text-primary-700 text-[10px] font-bold px-2 py-0.5 rounded border border-primary-100">
                                            {{ $permission->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">{{ __('messages.no_extra_permissions') }}</span>
                                    @endforelse
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-3">
                            @if($role->name !== 'Super Admin')
                                <button wire:click="editRole({{ $role->id }})" class="text-primary-600 hover:text-primary-800 font-bold transition">
                                    {{ __('messages.edit') }}
                                </button>
                                @if(!in_array($role->name, ['Agent', 'Customer']))
                                |
                                <button wire:click="deleteRole({{ $role->id }})" class="text-danger-600 hover:text-danger-800 font-bold transition" onclick="confirm('{{ __('messages.confirm_delete_role') }}') || event.stopImmediatePropagation()">
                                    {{ __('messages.delete') }}
                                </button>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">{{ __('messages.closed') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-bold">
                            {{ __('messages.no_roles_added') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <!-- Create / Edit Modal -->
    @if($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
                <div class="bg-white px-6 pt-5 pb-4 sm:p-8 sm:pb-6">
                    <h3 class="text-xl leading-6 font-black text-gray-900 mb-6 border-b pb-4" id="modal-title">
                        {{ $editingRoleId ? __('messages.edit_permissions') . $name : __('messages.create_new_role') }}
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <x-input-label value="{{ __('messages.role_name_en_preferred') }}" />
                            <x-text-input wire:model="name" type="text" class="mt-1 block w-full bg-slate-50 font-bold" autocomplete="off" placeholder="{{ __('messages.role_name_example') }}" @if($editingRoleId && in_array($name, ['Agent', 'Customer'])) readonly @endif />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            @if($editingRoleId && in_array($name, ['Agent', 'Customer']))
                                <p class="text-xs text-orange-500 mt-1 font-bold">{{ __('messages.cannot_change_basic_role_name') }}</p>
                            @endif
                        </div>
                        
                        <div class="bg-slate-50 p-6 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-700 mb-4">{{ __('messages.select_allowed_permissions') }}</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($allPermissions as $permission)
                                    <label class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-primary-500 transition-colors shadow-sm">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                        <span class="text-sm font-bold text-slate-700">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('selectedPermissions')" class="mt-2" />
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-3 border-t">
                    <button wire:click="saveRole" type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-primary-600 text-base font-bold text-white hover:bg-primary-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition">
                        {{ __('messages.save_changes') }}
                    </button>
                    <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm transition">
                        {{ __('messages.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
