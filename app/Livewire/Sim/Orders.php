<?php

namespace App\Livewire\Sim;

use App\Exceptions\SimOrderException;
use App\Livewire\Sim\Concerns\ShowsSupplierBalance;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Services\SimOrderService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Users with `manage_sims` see every order and can activate/reject pending ones.
 * Everyone else sees only their own orders.
 */
class Orders extends Component
{
    use WithPagination, ShowsSupplierBalance;

    // Orders are activated instantly, so everyone starts on "all"
    public string $statusFilter = 'all';
    public string $search = '';

    protected function canManage(): bool
    {
        return (bool) auth()->user()?->can('manage_sims');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('sim-order-created')]
    public function refreshOrders(): void
    {
        // Re-render only
    }

    public function complete(int $orderId, ?string $note = null): void
    {
        abort_unless($this->canManage(), 403);

        $order = SimOrder::findOrFail($orderId);
        $note = $note !== null ? trim(mb_substr($note, 0, 500)) : null;

        try {
            app(SimOrderService::class)->complete($order, auth()->user(), $note ?: null);
            session()->flash('sim_success', __('messages.sim_order_completed_flash', ['number' => $order->order_number]));
        } catch (SimOrderException $e) {
            session()->flash('sim_error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('SIM order complete failed: ' . $e->getMessage(), ['order_id' => $orderId]);
            session()->flash('sim_error', __('messages.sim_order_error_generic'));
        }
    }

    public function reject(int $orderId, ?string $reason = null): void
    {
        abort_unless($this->canManage(), 403);

        $reason = trim(mb_substr((string) $reason, 0, 500));
        if ($reason === '') {
            session()->flash('sim_error', __('messages.sim_reject_reason_required'));
            return;
        }

        $order = SimOrder::findOrFail($orderId);

        try {
            $order = app(SimOrderService::class)->reject($order, auth()->user(), $reason);
            session()->flash('sim_success', __('messages.sim_order_rejected_flash', [
                'number' => $order->order_number,
                'amount' => SimPackage::formatPrice($order->charged_amount),
            ]));
        } catch (SimOrderException $e) {
            session()->flash('sim_error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('SIM order reject failed: ' . $e->getMessage(), ['order_id' => $orderId]);
            session()->flash('sim_error', __('messages.sim_order_error_generic'));
        }
    }

    public function render()
    {
        $canManage = $this->canManage();

        $query = SimOrder::query()
            ->with([
                'user' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'processedBy:id,name',
                'package:id,supplier_code',
            ])
            ->latest();

        if (!$canManage) {
            $query->where('user_id', auth()->id());
        }

        if (in_array($this->statusFilter, SimOrder::STATUSES, true)) {
            $query->where('status', $this->statusFilter);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search, $canManage) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', '%' . preg_replace('/[\s\-]+/', '', $search) . '%')
                    ->orWhere('customer_name', 'like', "%{$search}%");

                if ($canManage) {
                    $q->orWhereHas('user', fn ($u) => $u->withTrashed()->where('name', 'like', "%{$search}%"));
                }
            });
        }

        return view('livewire.sim.orders', [
            'orders' => $query->paginate(15),
            'canManage' => $canManage,
            'pendingCount' => $canManage ? SimOrder::where('status', SimOrder::STATUS_PENDING)->count() : 0,
            // Our balance at kontor86 (also shows at a glance whether the API connection works)
            'supplierBalance' => $this->supplierBalance(),
        ]);
    }
}
