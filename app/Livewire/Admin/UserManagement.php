<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\ExchangeRateService;
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
    public string $username = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $role = 'Customer'; // Super Admin, Agent, Customer
    public float $balance = 0.0;
    public bool $has_unlimited_balance = false;
    public float $balance_limit = 0.0;
    // Special Vodafone Cash rate (TRY → EGP); empty = the general rate
    public string $custom_vodafone_rate = '';

    // Partner API (other systems order SIM packages with this account's balance)
    public bool $api_enabled = false;
    public string $api_allowed_ips = '';
    /** A key just created: shown once in the form, never stored in clear */
    public ?string $newApiToken = null;

    public bool $is_active = true;
    public bool $two_factor_enabled = false;
    public bool $receives_telegram_alerts = false;

    public bool $showFormModal = false;
    
    // Statement Modal
    public bool $showStatementModal = false;
    public array|\Illuminate\Database\Eloquent\Collection $statementTransactions = [];
    public string $statementUserName = '';
    public float $statementUserBalance = 0.0;

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
        $this->username = (string) $user->username; // accounts created by sign-up have no username
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->role = $user->roles->first()?->name ?? 'Customer';
        $this->balance = (float) $user->balance;
        $this->has_unlimited_balance = (bool) $user->has_unlimited_balance;
        $this->balance_limit = (float) $user->balance_limit;
        $this->custom_vodafone_rate = $user->hasCustomVodafoneRate() ? static::formatRate((float) $user->custom_vodafone_rate) : '';
        $this->api_enabled = (bool) $user->api_enabled;
        $this->api_allowed_ips = (string) $user->api_allowed_ips;
        $this->newApiToken = null;
        $this->is_active = $user->is_active;
        $this->two_factor_enabled = $user->two_factor_enabled;
        $this->receives_telegram_alerts = $user->hasDirectPermission('receive_telegram_alerts');
        
        $this->showFormModal = true;
    }

    public function saveUser(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'email' => 'nullable|email|max:255|unique:users,email' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'phone' => 'nullable|string|max:20|unique:users,phone' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'balance' => 'required|numeric',
            'has_unlimited_balance' => 'required|boolean',
            'balance_limit' => 'required_if:has_unlimited_balance,false|numeric|min:0',
            'role' => 'required|exists:roles,name',
            'custom_vodafone_rate' => 'nullable|numeric|min:0.0001|max:100000',
            'api_allowed_ips' => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) {
                foreach (array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $value))) as $ip) {
                    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                        $fail(__('messages.api_invalid_ip', ['ip' => $ip]));
                        return;
                    }
                }
            }],
        ];

        if (!$this->editingUserId || !empty($this->password)) {
            $rules['password'] = 'required|min:8';
        }

        $this->custom_vodafone_rate = trim(str_replace(',', '.', $this->custom_vodafone_rate));
        $this->validate($rules, [
            'custom_vodafone_rate.numeric' => __('messages.custom_rate_invalid'),
            'custom_vodafone_rate.min' => __('messages.custom_rate_invalid'),
            'custom_vodafone_rate.max' => __('messages.custom_rate_invalid'),
        ]);

        $data = [
            'name' => $this->name,
            'username' => $this->username,
            'email' => empty($this->email) ? null : $this->email,
            'phone' => empty($this->phone) ? null : $this->phone,
            'balance' => $this->balance,
            'has_unlimited_balance' => $this->has_unlimited_balance,
            'balance_limit' => $this->balance_limit,
            'is_active' => $this->is_active,
            'two_factor_enabled' => $this->two_factor_enabled,
            'api_enabled' => $this->api_enabled,
            'api_allowed_ips' => trim($this->api_allowed_ips) ?: null,
        ];

        // Special rate: fixed until changed by hand. Saving it records today's general rate,
        // so a reminder appears when the general rate moves away from it.
        if ($this->custom_vodafone_rate === '') {
            $data += ['custom_vodafone_rate' => null, 'custom_vodafone_rate_base' => null, 'custom_vodafone_rate_reviewed_at' => null];
        } else {
            $data += [
                'custom_vodafone_rate' => round((float) $this->custom_vodafone_rate, 5),
                'custom_vodafone_rate_base' => app(ExchangeRateService::class)->generalVodafoneRate(),
                'custom_vodafone_rate_reviewed_at' => now(),
            ];
        }

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            
            $balanceBefore = $user->balance;
            
            $user->update($data);
            $user->syncRoles([$this->role]);
            
            if ($this->receives_telegram_alerts) {
                $user->givePermissionTo('receive_telegram_alerts');
            } else {
                $user->revokePermissionTo('receive_telegram_alerts');
            }
            
            // Check if balance was manually changed
            if ((float)$balanceBefore !== (float)$this->balance) {
                \App\Models\BalanceTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => auth()->id(),
                    'type' => 'adjustment',
                    'amount' => abs((float)$this->balance - (float)$balanceBefore),
                    'balance_before' => $balanceBefore,
                    'balance_after' => $this->balance,
                    'notes' => 'تعديل يدوي للرصيد من إدارة المستخدمين',
                ]);
            }
            
            session()->flash('success', 'تم تحديث بيانات المستخدم بنجاح.');
        } else {
            $user = User::create($data);
            $user->assignRole($this->role);
            
            if ($this->receives_telegram_alerts) {
                $user->givePermissionTo('receive_telegram_alerts');
            }
            
            if ((float)$this->balance != 0) {
                \App\Models\BalanceTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => auth()->id(),
                    'type' => 'adjustment',
                    'amount' => abs((float)$this->balance),
                    'balance_before' => 0,
                    'balance_after' => $this->balance,
                    'notes' => 'رصيد افتتاحي عند إنشاء الحساب',
                ]);
            }
            
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

    public function openStatementModal(int $id): void
    {
        $user = User::findOrFail($id);
        $this->statementUserName = $user->name;
        $this->statementUserBalance = (float) $user->balance;
        
        $this->statementTransactions = \App\Models\BalanceTransaction::where('user_id', $user->id)
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $this->showStatementModal = true;
    }

    public function closeStatementModal(): void
    {
        $this->showStatementModal = false;
    }

    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            session()->flash('error', 'لا يمكنك حذف حسابك الخاص.');
            return;
        }

        $user->delete(); // This performs a soft delete because of the SoftDeletes trait
        session()->flash('success', 'تم حذف المستخدم بنجاح.');
    }

    /** Creates (or replaces) the account's partner API key; shown once in the form. */
    public function generateApiKey(): void
    {
        abort_unless($this->editingUserId, 404);
        $user = User::findOrFail($this->editingUserId);

        $this->newApiToken = $user->issueApiToken();
        $this->api_enabled = true;
    }

    /** The key stops working at once. */
    public function revokeApiKey(): void
    {
        abort_unless($this->editingUserId, 404);
        User::findOrFail($this->editingUserId)->revokeApiToken();

        $this->newApiToken = null;
        $this->api_enabled = false;
    }

    /** Keep this customer's special rate as it is, reviewed against today's general rate. */
    public function keepCustomRate(int $id): void
    {
        $user = User::findOrFail($id);
        $user->markCustomVodafoneRateReviewed(app(ExchangeRateService::class)->generalVodafoneRate());
        session()->flash('success', __('messages.custom_rate_kept', ['name' => $user->name]));
    }

    /** Keep every special rate that is waiting for review as it is. */
    public function keepAllCustomRates(): void
    {
        $general = app(ExchangeRateService::class)->generalVodafoneRate();
        $users = User::customVodafoneRatesToReview($general);

        foreach ($users as $user) {
            $user->markCustomVodafoneRateReviewed($general);
        }

        session()->flash('success', __('messages.custom_rate_all_kept', ['count' => $users->count()]));
    }

    /** 1.66000 → "1.66", 1.70000 → "1.70", 1.65500 → "1.655" (at least two decimals) */
    public static function formatRate(float $rate): string
    {
        [$whole, $decimals] = explode('.', number_format($rate, 5, '.', ''));

        return $whole . '.' . str_pad(rtrim($decimals, '0'), 2, '0');
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingUserId', 'name', 'username', 'email', 'phone', 'password', 'balance', 'has_unlimited_balance', 'balance_limit', 'custom_vodafone_rate', 'api_enabled', 'api_allowed_ips', 'newApiToken', 'role', 'is_active', 'two_factor_enabled', 'receives_telegram_alerts']);
        $this->resetValidation();
        $this->role = 'Customer';
    }

    public function render()
    {
        $query = User::query()->with('roles');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('username', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('phone', 'like', '%' . $this->searchQuery . '%');
            });
        }

        if ($this->roleFilter !== 'all') {
            $query->role($this->roleFilter);
        }

        $users = $query->latest()->paginate(10);
        $availableRoles = \Spatie\Permission\Models\Role::all();

        $generalVodafoneRate = app(ExchangeRateService::class)->generalVodafoneRate();
        $customRatesToReview = User::customVodafoneRatesToReview($generalVodafoneRate);
        $editingUser = $this->editingUserId ? User::find($this->editingUserId) : null;

        return view('livewire.admin.user-management', compact('users', 'availableRoles', 'generalVodafoneRate', 'customRatesToReview', 'editingUser'));
    }
}
