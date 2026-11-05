<?php

namespace App\Livewire\Sim;

use App\Exceptions\SimOrderException;
use App\Models\SimPackage;
use App\Services\SimOrderService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class BuyPackage extends Component
{
    public string $region = SimPackage::REGION_LOCAL;
    public ?int $selectedPackageId = null;

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
        return [
            'selectedPackageId' => ['required', 'integer'],
            // Local (05xx...) or international (+49...) numbers; spaces and dashes allowed
            'phoneNumber' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-]{6,19}$/'],
            'customerName' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
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

        $phone = preg_replace('/[\s\-]+/', '', $this->phoneNumber);

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

        $this->reset(['selectedPackageId', 'phoneNumber', 'customerName', 'notes']);
        auth()->user()->refresh();

        session()->flash('sim_order_success', __('messages.sim_order_created', ['number' => $order->order_number]));

        $this->dispatch('request-created');   // refreshes the balance widget & notifications
        $this->dispatch('sim-order-created'); // refreshes the orders list
    }

    public function render()
    {
        $packages = SimPackage::active()->ordered()->get();
        $user = auth()->user();

        return view('livewire.sim.buy-package', [
            'packagesByRegion' => $packages->groupBy('region'),
            'selectedPackage' => $this->selectedPackageId ? $packages->firstWhere('id', $this->selectedPackageId) : null,
            'isExemptFromCharge' => app(SimOrderService::class)->isExemptFromCharge($user),
        ]);
    }
}
