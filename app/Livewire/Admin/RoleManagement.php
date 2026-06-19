<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagement extends Component
{
    public $roles;
    public $allPermissions;
    
    public $showFormModal = false;
    public $editingRoleId = null;
    public $name = '';
    public $selectedPermissions = [];

    public function mount()
    {
        $this->allPermissions = Permission::all();
        $this->loadRoles();
    }

    public function loadRoles()
    {
        $this->roles = Role::withCount('users')->with('permissions')->get();
    }

    public function createNewRole()
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editRole($id)
    {
        $this->resetForm();
        $role = Role::with('permissions')->findOrFail($id);
        
        if ($role->name === 'Super Admin') {
            session()->flash('error', 'لا يمكن تعديل دور مدير النظام الأساسي.');
            return;
        }

        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        
        $this->showFormModal = true;
    }

    public function saveRole()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $this->editingRoleId,
            'selectedPermissions' => 'array',
        ]);

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            if ($role->name === 'Super Admin') {
                session()->flash('error', 'لا يمكن تعديل دور مدير النظام الأساسي.');
                return;
            }
            $role->update(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            session()->flash('success', 'تم تحديث الدور بنجاح.');
        } else {
            $role = Role::create(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            session()->flash('success', 'تم إضافة الدور بنجاح.');
        }

        $this->loadRoles();
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteRole($id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->name === 'Super Admin' || $role->name === 'Agent' || $role->name === 'Customer') {
            session()->flash('error', 'لا يمكن حذف الأدوار الأساسية للنظام.');
            return;
        }

        if ($role->users_count > 0) {
            session()->flash('error', 'لا يمكن حذف دور مرتبط بمستخدمين. الرجاء تغيير أدوارهم أولاً.');
            return;
        }

        $role->delete();
        session()->flash('success', 'تم حذف الدور بنجاح.');
        $this->loadRoles();
    }

    public function closeModal()
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset(['editingRoleId', 'name', 'selectedPermissions']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.role-management');
    }
}
