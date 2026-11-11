<?php

namespace App\Livewire\Sim;

use App\Models\SimPackage;
use Livewire\Component;

/** Admin screen to add / edit SIM packages, their AR/TR names and prices. */
class PackagesManager extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $region = SimPackage::REGION_LOCAL;
    public string $simType = SimPackage::TYPE_PHYSICAL;
    public string $networkName = '';
    public $dataGb = '';
    public $validityDays = 30;
    public $price = '';
    public string $nameAr = '';
    public string $nameTr = '';
    public string $descriptionAr = '';
    public string $descriptionTr = '';
    public string $supplierCode = '';
    // kontor86 product key (operator + tip + code) → automatic loading when all three are set
    public string $apiOperator = '';
    public string $apiTip = '';
    public string $apiCode = '';
    public bool $isActive = true;
    public $sortOrder = 0;
    public string $badge = '';

    public function mount(): void
    {
        $this->authorizeManage();
    }

    protected function authorizeManage(): void
    {
        abort_unless((bool) auth()->user()?->can('manage_sims'), 403);
    }

    protected function rules(): array
    {
        return [
            'region' => ['required', 'in:' . implode(',', SimPackage::REGIONS)],
            'simType' => ['required', 'in:' . implode(',', SimPackage::TYPES)],
            'networkName' => ['nullable', 'string', 'max:100'],
            'dataGb' => ['required', 'integer', 'min:1', 'max:10000'],
            'validityDays' => ['required', 'integer', 'min:1', 'max:3650'],
            'price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'nameAr' => ['nullable', 'string', 'max:255'],
            'nameTr' => ['nullable', 'string', 'max:255'],
            'descriptionAr' => ['nullable', 'string', 'max:1000'],
            'descriptionTr' => ['nullable', 'string', 'max:1000'],
            'supplierCode' => ['nullable', 'string', 'max:255'],
            'apiOperator' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'apiTip' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'apiCode' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'isActive' => ['boolean'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'badge' => ['nullable', 'in:' . implode(',', SimPackage::BADGES)],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => __('messages.sim_validation_required'),
            'integer' => __('messages.sim_validation_number'),
            'numeric' => __('messages.sim_validation_number'),
            'min' => __('messages.sim_validation_min'),
            'max' => __('messages.sim_validation_max'),
            'in' => __('messages.sim_validation_invalid'),
            'regex' => __('messages.sim_validation_invalid'),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'region' => __('messages.sim_region'),
            'simType' => __('messages.sim_type'),
            'networkName' => __('messages.sim_network'),
            'dataGb' => __('messages.sim_data_gb'),
            'validityDays' => __('messages.sim_validity_days'),
            'price' => __('messages.sim_price'),
            'nameAr' => __('messages.sim_name_ar'),
            'nameTr' => __('messages.sim_name_tr'),
            'descriptionAr' => __('messages.sim_description_ar'),
            'descriptionTr' => __('messages.sim_description_tr'),
            'supplierCode' => __('messages.sim_supplier_code'),
            'apiOperator' => __('messages.sim_api_operator'),
            'apiTip' => __('messages.sim_api_tip'),
            'apiCode' => __('messages.sim_api_code'),
            'sortOrder' => __('messages.sim_sort_order'),
            'badge' => __('messages.sim_badge'),
        ];
    }

    public function create(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->sortOrder = (int) SimPackage::withTrashed()->max('sort_order') + 1;
        $this->showForm = true;
    }

    public function edit(int $packageId): void
    {
        $this->authorizeManage();
        $package = SimPackage::findOrFail($packageId);

        $this->resetValidation();
        $this->editingId = $package->id;
        $this->region = $package->region;
        $this->simType = $package->sim_type;
        $this->networkName = (string) $package->network_name;
        $this->dataGb = $package->data_gb;
        $this->validityDays = $package->validity_days;
        $this->price = (float) $package->price;
        $this->nameAr = $package->name_ar;
        $this->nameTr = $package->name_tr;
        $this->descriptionAr = (string) $package->description_ar;
        $this->descriptionTr = (string) $package->description_tr;
        $this->supplierCode = (string) $package->supplier_code;
        $this->apiOperator = (string) $package->api_operator;
        $this->apiTip = (string) $package->api_tip;
        $this->apiCode = (string) $package->api_code;
        $this->isActive = $package->is_active;
        $this->sortOrder = $package->sort_order;
        $this->badge = (string) $package->badge;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();

        // Pasted values often carry spaces: clean before validating
        $this->apiOperator = trim($this->apiOperator);
        $this->apiTip = trim($this->apiTip);
        $this->apiCode = trim($this->apiCode);

        $this->validate();

        // The API product key only works complete: all three or none
        $apiParts = array_map('trim', [$this->apiOperator, $this->apiTip, $this->apiCode]);
        $filled = count(array_filter($apiParts, fn ($v) => $v !== ''));
        if ($filled > 0 && $filled < 3) {
            $this->addError('apiCode', __('messages.sim_api_fields_incomplete'));
            return;
        }

        $generated = SimPackage::generateNames($this->region, $this->simType, (int) $this->dataGb, (int) $this->validityDays);

        $data = [
            'region' => $this->region,
            'sim_type' => $this->simType,
            'network_name' => trim($this->networkName) ?: null,
            'data_gb' => (int) $this->dataGb,
            'validity_days' => (int) $this->validityDays,
            'price' => round((float) $this->price, 2),
            'name_ar' => trim($this->nameAr) !== '' ? trim($this->nameAr) : $generated['name_ar'],
            'name_tr' => trim($this->nameTr) !== '' ? trim($this->nameTr) : $generated['name_tr'],
            'description_ar' => trim($this->descriptionAr) ?: null,
            'description_tr' => trim($this->descriptionTr) ?: null,
            'supplier_code' => trim($this->supplierCode) ?: null,
            'api_operator' => strtolower($apiParts[0]) ?: null,
            'api_tip' => strtolower($apiParts[1]) ?: null,
            'api_code' => $apiParts[2] ?: null,
            'is_active' => $this->isActive,
            'sort_order' => (int) ($this->sortOrder ?: 0),
            'badge' => $this->badge ?: null,
        ];

        if ($this->editingId) {
            SimPackage::findOrFail($this->editingId)->update($data);
        } else {
            SimPackage::create($data);
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('sim_package_success', __('messages.sim_package_saved'));
    }

    public function toggleActive(int $packageId): void
    {
        $this->authorizeManage();
        $package = SimPackage::findOrFail($packageId);
        $package->update(['is_active' => !$package->is_active]);
    }

    public function delete(int $packageId): void
    {
        $this->authorizeManage();
        // Soft delete: existing orders keep their snapshot and link
        SimPackage::findOrFail($packageId)->delete();
        session()->flash('sim_package_success', __('messages.sim_package_deleted'));
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'editingId', 'region', 'simType', 'networkName', 'dataGb', 'validityDays', 'price',
            'nameAr', 'nameTr', 'descriptionAr', 'descriptionTr', 'supplierCode', 'apiOperator', 'apiTip', 'apiCode', 'isActive', 'sortOrder', 'badge',
        ]);
    }

    public function render()
    {
        $packages = SimPackage::withCount('orders')->ordered()->get();

        return view('livewire.sim.packages-manager', [
            'packagesByRegion' => $packages->groupBy('region'),
            'knownNetworks' => SimPackage::withTrashed()->whereNotNull('network_name')->distinct()->orderBy('network_name')->pluck('network_name'),
        ]);
    }
}
