<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public string $searchQuery = '';
    public string $roleFilter = 'all';

    // Form fields for create/edit
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $role = 'customer'; // admin, agent, customer
    public bool $is_active = true;

    public bool $showFormModal = false;

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function createNewUser(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editUser(int $id): void
    {
        $this->resetForm();
        $user = User::findOrFail($id);
        
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->role = $user->role;
        $this->is_active = $user->is_active;
        
        $this->showFormModal = true;
    }

    public function saveUser(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'phone' => 'nullable|string|max:20|unique:users,phone' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'role' => 'required|in:admin,agent,customer',
        ];

        if (!$this->editingUserId || !empty($this->password)) {
            $rules['password'] = 'required|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            User::findOrFail($this->editingUserId)->update($data);
            session()->flash('success', 'تم تحديث بيانات المستخدم بنجاح.');
        } else {
            User::create($data);
            session()->flash('success', 'تم إضافة المستخدم بنجاح.');
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            session()->flash('error', 'لا يمكنك إيقاف حسابك الخاص.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);
        session()->flash('success', 'تم تغيير حالة المستخدم بنجاح.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingUserId', 'name', 'email', 'phone', 'password', 'role', 'is_active']);
        $this->resetValidation();
    }

    public function render()
    {
        $query = User::query();

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('email', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('phone', 'like', '%' . $this->searchQuery . '%');
            });
        }

        if ($this->roleFilter !== 'all') {
            $query->where('role', $this->roleFilter);
        }

        $users = $query->latest()->paginate(10);

        return view('livewire.admin.user-management', compact('users'));
    }
}
