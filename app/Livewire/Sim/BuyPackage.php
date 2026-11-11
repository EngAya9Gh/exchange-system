<?php

namespace App\Livewire\Sim;

use App\Exceptions\SimOrderException;
use App\Livewire\Sim\Concerns\ShowsSupplierBalance;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Services\SimOrderService;
use Illuminate\Support\Facades\Log;
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

    public function selectRegion(string $region): void
    {
        if (!in_array($region, SimPackage::REGIONS, true)) {
            return;
        }

        $this->region = $region;
        $this->selectedPackageId = null;
        $this->resetValidation();
    }

    public function selectPackage(int $packageId): void
    {
        $this->selectedPackageId = SimPackage::active()->whereKey($packageId)->exists() ? $packageId : null;
        $this->resetValidation();
    }

    public function clearSelection(): void
    {
        $this->selectedPackageId = null;
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
            // eSIM: any local or international number (+49...); spaces and dashes allowed
            $phoneRules[] = 'regex:/^\+?[0-9][0-9\s\-()]{6,19}$/';
        }

        return [
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
                trim($this->notes)
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

        $this->reset(['selectedPackageId', 'phoneNumber', 'customerName', 'notes']);

        $message = match ($order->api_status) {
            SimOrder::API_PROCESSING, SimOrder::API_SUCCESS => 'sim_order_sent_api',
            SimOrder::API_UNKNOWN => 'sim_order_checking_api',
            default => 'sim_order_created',
        };
        session()->flash('sim_order_success', __("messages.{$message}", ['number' => $order->order_number]));
    }

    public function render()
    {
        $packages = SimPackage::active()->ordered()->get();
        $user = auth()->user();

        return view('livewire.sim.buy-package', [
            'packagesByRegion' => $packages->groupBy('region'),
            'selectedPackage' => $this->selectedPackageId ? $packages->firstWhere('id', $this->selectedPackageId) : null,
            'isExemptFromCharge' => app(SimOrderService::class)->isExemptFromCharge($user),
            'supplierBalance' => $this->supplierBalance(),
            // "⚡ instant loading" chip on packages sent automatically through kontor86
            'apiConfigured' => app(\App\Services\KontorApiService::class)->isConfigured(),
        ]);
    }
}
