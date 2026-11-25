<?php

namespace App\Livewire\Sim;

use App\Exceptions\SimOrderException;
use App\Livewire\Sim\Concerns\ShowsSupplierBalance;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Services\KontorApiService;
use App\Services\SimOrderService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BuyPackage extends Component
{
    use ShowsSupplierBalance;

    public string $region = SimPackage::REGION_LOCAL;
    public ?int $selectedPackageId = null;

    /**
     * Fixed start of physical (Tele CARD) line numbers, shown next to the input.
     * The user types only the remaining 8 digits.
     */
    public const PHONE_PREFIX = SimPackage::PHYSICAL_PHONE_PREFIX;

    /** Physical SIM: the last 8 digits only. eSIM: the full number. */
    public string $phoneNumber = '';
    public string $customerName = '';
    public string $notes = '';

    /** SIM managers only: send this order to one supplier ('' = automatic routing from the settings). */
    public string $supplierChoice = '';

    /** The order just placed, shown in the confirmation window until it is closed (always the buyer's own). */
    #[Locked]
    public ?int $lastOrderId = null;

    protected function canChooseSupplier(): bool
    {
        return (bool) auth()->user()?->can('manage_sims');
    }

    /** $region holds the open tab: "local", "local_esim" (Turkey eSIM), "europe", "europe_esim". */
    public function selectRegion(string $region): void
    {
        if (!in_array($region, SimPackage::TABS, true)) {
            return;
        }

        $this->region = $region;
        $this->selectedPackageId = null;
        $this->supplierChoice = '';
        $this->resetValidation();
    }

    public function selectPackage(int $packageId): void
    {
        $package = SimPackage::active()->find($packageId);

        $this->selectedPackageId = $package?->id;
        $this->supplierChoice = '';
        if ($package) {
            $this->region = $package->tabKey(); // keep the selected card in view
        }
        $this->resetValidation();
    }

    public function closeReceipt(): void
    {
        $this->lastOrderId = null;
    }

    /** "View order history" in the confirmation window: the host dashboard switches to the orders tab. */
    public function showOrders(): void
    {
        $this->lastOrderId = null;
        $this->dispatch('sim-show-orders');
    }

    /**
     * What the confirmation window says about the order:
     * loading (sent, loading automatically) · checking (outcome being checked) · loaded (the supplier confirmed)
     * · manual (activated, the team loads it from the group) · queued (legacy pending order) · failed (refunded).
     */
    public static function receiptState(SimOrder $order): string
    {
        return match (true) {
            $order->status === SimOrder::STATUS_REJECTED => 'failed',
            $order->api_status === SimOrder::API_SUCCESS => 'loaded',
            $order->api_status === SimOrder::API_UNKNOWN => 'checking',
            $order->api_status === SimOrder::API_PROCESSING => 'loading',
            $order->status === SimOrder::STATUS_COMPLETED => 'manual',
            default => 'queued',
        };
    }

    public function clearSelection(): void
    {
        $this->selectedPackageId = null;
        $this->supplierChoice = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        $phoneRules = ['bail', 'required', 'string', 'max:30'];

        if ($this->selectedIsPhysical()) {
            // "50" is fixed: exactly 8 more digits (a pasted full 50XXXXXXXX number is accepted too)
            $phoneRules[] = function (string $attribute, mixed $value, \Closure $fail) {
                if (static::lastEightDigits((string) $value) === null) {
                    $fail(__('messages.sim_invalid_last8'));
                }
            };
        } else {
            // eSIM: the supplier texts the install link to a TURKISH mobile (its "GSM NO" / gsmno is 5XXXXXXXXX).
            // 0555…, +90 555…, 0090… are accepted and stored as 5551234567.
            $phoneRules[] = function (string $attribute, mixed $value, \Closure $fail) {
                if (!preg_match('/^5\d{9}$/', static::normalizePhone((string) $value))) {
                    $fail(__('messages.sim_invalid_phone_esim'));
                }
            };
        }

        return [
            'supplierChoice' => ['nullable', 'in:' . implode(',', KontorApiService::SUPPLIERS)],
            'selectedPackageId' => ['required', 'integer'],
            'phoneNumber' => $phoneRules,
            'customerName' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function selectedIsPhysical(): bool
    {
        return $this->selectedPackageId
            && SimPackage::whereKey($this->selectedPackageId)->value('sim_type') === SimPackage::TYPE_PHYSICAL;
    }

    /**
     * Digits typed after the fixed "50": "51234567" → "51234567".
     * Also accepts a pasted full number: "0505 123 45 67", "+90 505 123 45 67", "5051234567" → "51234567".
     */
    public static function lastEightDigits(string $input): ?string
    {
        $digits = static::normalizePhone($input);

        if (preg_match('/^\d{8}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^' . self::PHONE_PREFIX . '(\d{8})$/', $digits, $m)) {
            return $m[1];
        }

        return null;
    }

    /** "0505 123 45 67", "+90 505 1234567" → "5051234567" (international non-TR numbers are kept). */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[\s\-().]+/', '', trim($phone));

        return match (true) {
            str_starts_with($digits, '+90') => substr($digits, 3),
            str_starts_with($digits, '0090') => substr($digits, 4),
            strlen($digits) === 12 && str_starts_with($digits, '90') => substr($digits, 2),
            strlen($digits) === 11 && str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };
    }

    protected function messages(): array
    {
        return [
            'selectedPackageId.required' => __('messages.sim_choose_package_error'),
            'phoneNumber.required' => __('messages.sim_phone_required'),
            'phoneNumber.regex' => __('messages.sim_invalid_phone'),
            'phoneNumber.max' => __('messages.sim_invalid_phone'),
            'customerName.max' => __('messages.sim_text_too_long'),
            'notes.max' => __('messages.sim_text_too_long'),
        ];
    }

    public function placeOrder(): void
    {
        $this->validate();

        $phone = $this->selectedIsPhysical()
            ? self::PHONE_PREFIX . static::lastEightDigits($this->phoneNumber)
            : static::normalizePhone($this->phoneNumber);

        try {
            $order = app(SimOrderService::class)->placeOrder(
                auth()->user(),
                (int) $this->selectedPackageId,
                $phone,
                trim($this->customerName),
                trim($this->notes),
                null,
                $this->canChooseSupplier() && $this->supplierChoice !== '' ? $this->supplierChoice : null
            );
        } catch (SimOrderException $e) {
            $this->addError('order', $e->getMessage());
            return;
        } catch (\Throwable $e) {
            Log::error('SIM order failed: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            $this->addError('order', __('messages.sim_order_error'));
            return;
        }

        auth()->user()->refresh();
        $this->dispatch('request-created');   // refreshes the balance widget & notifications
        $this->dispatch('sim-order-created'); // refreshes the orders list

        // The supplier refused the automatic load: already refunded, keep the form so they can fix & retry
        if ($order->status === SimOrder::STATUS_REJECTED) {
            $this->addError('order', __('messages.sim_api_failed_customer', ['reason' => SimOrder::publicSupplierReason($order->api_message)]));
            return;
        }

        $this->reset(['selectedPackageId', 'phoneNumber', 'customerName', 'notes', 'supplierChoice']);
        $this->lastOrderId = $order->id; // opens the confirmation window
    }

    public function render()
    {
        $packages = SimPackage::active()->ordered()->get();
        $user = auth()->user();

        $packagesByTab = $packages->groupBy(fn (SimPackage $package) => $package->tabKey());

        $lastOrder = $this->lastOrderId
            ? SimOrder::with('package')->where('user_id', $user->id)->find($this->lastOrderId)
            : null;

        return view('livewire.sim.buy-package', [
            'packagesByTab' => $packagesByTab,
            'tabs' => SimPackage::visibleTabs($packagesByTab),
            'selectedPackage' => $this->selectedPackageId ? $packages->firstWhere('id', $this->selectedPackageId) : null,
            'isExemptFromCharge' => app(SimOrderService::class)->isExemptFromCharge($user),
            'supplierBalances' => $this->supplierBalances(),
            // "⚡ instant loading" chip on packages sent automatically through kontor86
            // Suppliers sending orders right now (a package with a key at one of them loads automatically)
            'autoSuppliers' => array_keys(array_filter(\App\Services\KontorApiService::overview(), fn (array $s) => $s['active'])),
            'supplierOptions' => $this->supplierOptions($packages->firstWhere('id', $this->selectedPackageId)),
            'lastOrder' => $lastOrder,
            'receiptState' => $lastOrder ? static::receiptState($lastOrder) : null,
            'showReceiptSupplier' => $this->canChooseSupplier(),
        ]);
    }

    /**
     * The supplier picker for SIM managers: where the order goes automatically, and each supplier with
     * its listed cost, state and whether it carries this package. Null for everyone else.
     */
    protected function supplierOptions(?SimPackage $package): ?array
    {
        if (!$package || !$this->canChooseSupplier()) {
            return null;
        }

        $auto = null;
        foreach (KontorApiService::inPriorityOrder($package->preferred_supplier) as $supplier => $api) {
            if ($package->isApiReadyFor($supplier) && $api->autoSendActive()) {
                $auto = $supplier;
                break;
            }
        }

        $suppliers = [];
        foreach (KontorApiService::SUPPLIERS as $supplier) {
            $api = KontorApiService::for($supplier);
            $suppliers[$supplier] = [
                'linked' => $package->isApiReadyFor($supplier),
                'configured' => $api->isConfigured(),
                'paused' => $api->isAutoSendPaused(),
                'cost' => $package->listedCost($supplier),
            ];
        }

        return ['auto' => $auto, 'suppliers' => $suppliers];
    }
}
