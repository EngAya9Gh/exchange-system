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
    // telekomtl product key (same three parts, its own codes)
    public string $tkOperator = '';
    public string $tkTip = '';
    public string $tkCode = '';
    // Supplier tried first for this package ('' = the primary supplier)
    public string $preferredSupplier = '';

    // Product picked from each supplier's list ("tip|code"): fills the three key fields
    public string $pickKontor = '';
    public string $pickTk = '';
    public bool $isActive = true;
    public $sortOrder = 0;
    public string $badge = '';
    // «⚡ سريع · Hızlı»: own card colors, linked only to kontor86 HIZLI FİZİKSEL products
    public bool $isFast = false;

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
            // Codes may contain a dot (telekomtl: 100.01, 200.04)
            'apiCode' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/'],
            'tkOperator' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'tkTip' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'tkCode' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/'],
            'preferredSupplier' => ['nullable', 'in:' . implode(',', \App\Services\KontorApiService::SUPPLIERS)],
            'isActive' => ['boolean'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'badge' => ['nullable', 'in:' . implode(',', SimPackage::BADGES)],
            'isFast' => ['boolean'],
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
            'tkOperator' => __('messages.sim_api_operator'),
            'tkTip' => __('messages.sim_api_tip'),
            'tkCode' => __('messages.sim_api_code'),
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
        $this->tkOperator = (string) $package->telekomtl_operator;
        $this->tkTip = (string) $package->telekomtl_tip;
        $this->tkCode = (string) $package->telekomtl_code;
        $this->preferredSupplier = (string) $package->preferred_supplier;
        $this->pickKontor = $this->pickValue('kontor86', $this->apiTip, $this->apiCode);
        $this->pickTk = $this->pickValue('telekomtl', $this->tkTip, $this->tkCode);
        $this->isActive = $package->is_active;
        $this->sortOrder = $package->sort_order;
        $this->badge = (string) $package->badge;
        $this->isFast = (bool) $package->is_fast;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();

        // Pasted values often carry spaces: clean before validating
        $this->apiOperator = trim($this->apiOperator);
        $this->apiTip = trim($this->apiTip);
        $this->apiCode = trim($this->apiCode);
        $this->tkOperator = trim($this->tkOperator);
        $this->tkTip = trim($this->tkTip);
        $this->tkCode = trim($this->tkCode);

        $this->validate();

        // A product key only works complete: all three or none (per supplier)
        $apiParts = [$this->apiOperator, $this->apiTip, $this->apiCode];
        $tkParts = [$this->tkOperator, $this->tkTip, $this->tkCode];
        foreach (['apiCode' => $apiParts, 'tkCode' => $tkParts] as $errorField => $parts) {
            $filled = count(array_filter($parts, fn ($v) => $v !== ''));
            if ($filled > 0 && $filled < 3) {
                $this->addError($errorField, __('messages.sim_api_fields_incomplete'));
                return;
            }
        }

        $generated = SimPackage::generateNames($this->region, $this->simType, (int) $this->dataGb, (int) $this->validityDays, $this->isFast);

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
            // Exactly as typed (only trimmed): same letters as the supplier panel, e.g. Callback / eSIM
            'api_operator' => $apiParts[0] ?: null,
            'api_tip' => $apiParts[1] ?: null,
            'api_code' => $apiParts[2] ?: null,
            'telekomtl_operator' => $tkParts[0] ?: null,
            'telekomtl_tip' => $tkParts[1] ?: null,
            'telekomtl_code' => $tkParts[2] ?: null,
            'preferred_supplier' => $this->preferredSupplier ?: null,
            'is_active' => $this->isActive,
            'sort_order' => (int) ($this->sortOrder ?: 0),
            'badge' => $this->badge ?: null,
            'is_fast' => $this->isFast,
        ];

        $linked = [];
        if ($this->editingId) {
            SimPackage::findOrFail($this->editingId)->update($data);
        } else {
            // A new package with no key is linked to the one supplier product with the same specs, if any
            $linked = SimPackage::create($data)->applySuggestedLinks();
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('sim_package_success', $linked
            ? __('messages.sim_package_saved_linked', ['products' => $this->describeLinks($linked)])
            : __('messages.sim_package_saved'));
    }

    /** Links an unlinked package to its matching supplier product(s) in one click. */
    public function linkSuggested(int $packageId): void
    {
        $this->authorizeManage();
        $package = SimPackage::findOrFail($packageId);
        $linked = $package->applySuggestedLinks();

        session()->flash('sim_package_success', $linked
            ? __('messages.sim_package_linked', ['name' => $package->name_ar, 'products' => $this->describeLinks($linked)])
            : __('messages.sim_package_no_match', ['name' => $package->name_ar]));
    }

    public function linkAllSuggested(): void
    {
        $this->authorizeManage();
        $count = 0;
        foreach (SimPackage::all() as $package) {
            if (!$package->isApiReady() && $package->applySuggestedLinks()) {
                $count++;
            }
        }

        session()->flash('sim_package_success', __('messages.sim_packages_linked_count', ['count' => $count]));
    }

    /** "kontor86: FİZİKSEL Turkiye 5 GB · 30 gün (26240)" */
    protected function describeLinks(array $links): string
    {
        return collect($links)->map(fn (array $p, string $supplier) => "{$supplier}: {$p['name']} ({$p['code']})")->implode(' · ');
    }

    public function updatedPickKontor(string $value): void
    {
        [$this->apiOperator, $this->apiTip, $this->apiCode] = $this->keyFromPick('kontor86', $value);
        $this->resetValidation(['apiOperator', 'apiTip', 'apiCode']);
    }

    public function updatedPickTk(string $value): void
    {
        [$this->tkOperator, $this->tkTip, $this->tkCode] = $this->keyFromPick('telekomtl', $value);
        $this->resetValidation(['tkOperator', 'tkTip', 'tkCode']);
    }

    /** "TeleCard|26240" → ['Callback', 'TeleCard', '26240']; '' → empty key (not linked to this supplier) */
    protected function keyFromPick(string $supplier, string $value): array
    {
        [$tip, $code] = array_pad(explode('|', $value, 2), 2, '');
        $product = $value === '' ? null : SimPackage::findSupplierProduct($supplier, $tip, $code);

        return $product ? [$product['operator'], $product['tip'], (string) $product['code']] : ['', '', ''];
    }

    protected function pickValue(string $supplier, string $tip, string $code): string
    {
        $product = SimPackage::findSupplierProduct($supplier, $tip, $code);

        return $product ? $product['tip'] . '|' . $product['code'] : '';
    }

    /**
     * The supplier's API code typed into «اسم الباقة عند المورد» (a note field never sent to anyone): the product it
     * belongs to, while that supplier's key fields do not hold it yet. Null otherwise.
     * (2026-11-26: the teacher typed 2285493 there, so his package was linked to the ordinary products instead.)
     */
    protected function supplierCodeProduct(): ?array
    {
        $code = trim($this->supplierCode);
        if ($code === '') {
            return null;
        }

        foreach (array_keys(SimPackage::API_KEY_COLUMNS) as $supplier) {
            foreach (SimPackage::supplierProducts($supplier) as $product) {
                if ((string) $product['code'] !== $code) {
                    continue;
                }
                [$tip, $keyCode] = $supplier === 'kontor86' ? [$this->apiTip, $this->apiCode] : [$this->tkTip, $this->tkCode];

                return $tip === $product['tip'] && $keyCode === (string) $product['code'] ? null : $product + ['supplier' => $supplier];
            }
        }

        return null;
    }

    /** Moves that code to the automatic-loading fields; a HIZLI product also makes the package «⚡ سريع». */
    public function useSupplierCodeAsKey(): void
    {
        $this->authorizeManage();
        $product = $this->supplierCodeProduct();
        if (!$product) {
            return;
        }

        $pick = $product['tip'] . '|' . $product['code'];
        if ($product['supplier'] === 'kontor86') {
            $this->pickKontor = $pick;
            $this->updatedPickKontor($pick);
        } else {
            $this->pickTk = $pick;
            $this->updatedPickTk($pick);
        }

        if (!empty($product['fast'])) {
            // Fast packages take only HIZLI products: an ordinary product at the other supplier would load the wrong one
            $this->isFast = true;
            $this->pickTk = '';
            $this->updatedPickTk('');
        }
        $this->supplierCode = $product['name'];
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
            'nameAr', 'nameTr', 'descriptionAr', 'descriptionTr', 'supplierCode', 'apiOperator', 'apiTip', 'apiCode', 'tkOperator', 'tkTip', 'tkCode', 'preferredSupplier', 'pickKontor', 'pickTk', 'isActive', 'sortOrder', 'badge', 'isFast',
        ]);
    }

    public function render()
    {
        $packages = SimPackage::withCount('orders')->ordered()->get();

        return view('livewire.sim.packages-manager', [
            // On sale but linked to no supplier: every order of these is loaded by hand
            'unlinked' => $packages->filter(fn (SimPackage $p) => $p->is_active && !$p->isApiReady())->values(),
            // Products with the same specs as the package in the form (region, type, network, GB, days)
            'suggestions' => $this->showForm && is_numeric($this->dataGb) && is_numeric($this->validityDays)
                ? collect(array_keys(SimPackage::API_KEY_COLUMNS))->mapWithKeys(fn (string $supplier) => [$supplier => (function () use ($supplier) {
                    $matches = (new SimPackage([
                        'region' => $this->region, 'sim_type' => $this->simType, 'network_name' => $this->networkName,
                        'data_gb' => (int) $this->dataGb, 'validity_days' => (int) $this->validityDays, 'is_fast' => $this->isFast,
                    ]))->matchingProducts($supplier);

                    return count($matches) === 1 ? $matches[0] : null;
                })()])->all()
                : [],
            'supplierCodeProduct' => $this->showForm ? $this->supplierCodeProduct() : null,
            'catalogue' => [
                'kontor86' => collect(SimPackage::supplierProducts('kontor86'))->groupBy('group'),
                'telekomtl' => collect(SimPackage::supplierProducts('telekomtl'))->groupBy('group'),
            ],
            'packagesByTab' => $packagesByTab = $packages->groupBy(fn (SimPackage $package) => $package->tabKey()),
            'tabs' => SimPackage::visibleTabs($packagesByTab),
            'knownNetworks' => SimPackage::withTrashed()->whereNotNull('network_name')->distinct()->orderBy('network_name')->pluck('network_name'),
        ]);
    }
}
