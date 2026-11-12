<?php

namespace App\Livewire\Sim;

use App\Exceptions\SimOrderException;
use App\Livewire\Sim\Concerns\ShowsSupplierBalance;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Services\SimOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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

    public const PERIODS = ['all', 'today', 'yesterday', '7d', '30d', 'month', 'last_month', 'custom'];

    // Orders are activated instantly, so everyone starts on "all"
    public string $statusFilter = 'all';
    public string $search = '';

    // Date filter (list + statistics)
    public string $period = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';

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

    public function updatedPeriod(): void
    {
        if (!in_array($this->period, self::PERIODS, true)) {
            $this->period = 'all';
        }
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} start / end of the chosen period (null = open) */
    protected function dateRange(): array
    {
        $now = now();

        return match ($this->period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'custom' => $this->customRange(),
            default => [null, null],
        };
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    protected function customRange(): array
    {
        $parse = function (string $value): ?Carbon {
            try {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? Carbon::createFromFormat('Y-m-d', $value) : null;
            } catch (\Throwable) {
                return null;
            }
        };

        $from = $parse($this->dateFrom)?->startOfDay();
        $to = $parse($this->dateTo)?->endOfDay();

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /** Orders this user may see, narrowed by search and date (not by status: the statistics split by status). */
    protected function baseQuery(bool $canManage): Builder
    {
        $query = SimOrder::query();

        if (!$canManage) {
            $query->where('user_id', auth()->id());
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

        [$from, $to] = $this->dateRange();
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Figures for the stat cards.
     *  - sales: what buyers paid for orders that were not refunded
     *  - profit: paid − supplier cost, only for orders whose cost the supplier gave us (automatic loads)
     *  - refunded: money returned for refused / cancelled orders
     */
    protected function statistics(Builder $base): array
    {
        $rejected = SimOrder::STATUS_REJECTED;
        $open = "'" . implode("','", SimOrder::API_OPEN_STATES) . "'";
        $costed = "status != '{$rejected}' AND api_cost > 0 AND charged_amount > 0";

        $row = (clone $base)->toBase()->selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status != '{$rejected}' THEN 1 ELSE 0 END) AS done_count,
            SUM(CASE WHEN status = '{$rejected}' THEN 1 ELSE 0 END) AS refunded_count,
            SUM(CASE WHEN status != '{$rejected}' AND api_status IN ({$open}) THEN 1 ELSE 0 END) AS open_count,
            SUM(CASE WHEN status != '{$rejected}' THEN charged_amount ELSE 0 END) AS sales,
            SUM(CASE WHEN status = '{$rejected}' THEN charged_amount ELSE 0 END) AS refunded,
            SUM(CASE WHEN {$costed} THEN 1 ELSE 0 END) AS costed_count,
            SUM(CASE WHEN {$costed} THEN charged_amount ELSE 0 END) AS costed_sales,
            SUM(CASE WHEN {$costed} THEN api_cost ELSE 0 END) AS costed_cost
        ")->first();

        $doneCount = (int) ($row->done_count ?? 0);
        $sales = (float) ($row->sales ?? 0);
        $costedSales = (float) ($row->costed_sales ?? 0);
        $costedCost = (float) ($row->costed_cost ?? 0);

        return [
            'total' => (int) ($row->total ?? 0),
            'done_count' => $doneCount,
            'refunded_count' => (int) ($row->refunded_count ?? 0),
            'open_count' => (int) ($row->open_count ?? 0),
            'sales' => $sales,
            'average' => $doneCount > 0 ? $sales / $doneCount : 0.0,
            'refunded' => (float) ($row->refunded ?? 0),
            'costed_count' => (int) ($row->costed_count ?? 0),
            'costed_sales' => $costedSales,
            'costed_cost' => $costedCost,
            'profit' => $costedSales - $costedCost,
        ];
    }

    /**
     * Best-selling packages in the period (not refunded), for the team:
     * orders, sales and share of the period's orders, with the package for a short title ("20 GB · 30 يوم").
     */
    protected function topPackages(Builder $base, int $doneCount): \Illuminate\Support\Collection
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'package_name_ar' : 'package_name_tr';

        $rows = (clone $base)->toBase()
            ->where('status', '!=', SimOrder::STATUS_REJECTED)
            ->selectRaw("sim_package_id, MAX({$nameColumn}) AS name, COUNT(*) AS orders_count, SUM(charged_amount) AS sales")
            ->groupBy('sim_package_id')
            ->orderByDesc('orders_count')
            ->orderByDesc('sales')
            ->limit(3)
            ->get();

        $packages = SimPackage::withTrashed()->whereIn('id', $rows->pluck('sim_package_id')->filter())->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'name' => $row->name,
            'package' => $packages->get($row->sim_package_id),
            'orders_count' => (int) $row->orders_count,
            'sales' => (float) $row->sales,
            'share' => $doneCount > 0 ? (int) round($row->orders_count * 100 / $doneCount) : 0,
        ]);
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

    /** Ask kontor86 now about an automatic load that is still open (also runs every minute in the background). */
    public function checkSupplier(int $orderId): void
    {
        abort_unless($this->canManage(), 403);

        $order = SimOrder::findOrFail($orderId);

        try {
            $state = app(SimOrderService::class)->checkSupplierStatus($order);
            session()->flash('sim_success', __('messages.sim_api_checked', [
                'number' => $order->order_number,
                'state' => __('messages.sim_api_state_' . ($state ?: 'unknown')),
            ]));
        } catch (\Throwable $e) {
            Log::error('SIM supplier check failed: ' . $e->getMessage(), ['order_id' => $orderId]);
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
        $base = $this->baseQuery($canManage);

        $query = (clone $base)
            ->with([
                'user' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'processedBy:id,name',
                'package:id,supplier_code',
            ])
            ->latest();

        if (in_array($this->statusFilter, SimOrder::STATUSES, true)) {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->paginate(15);

        return view('livewire.sim.orders', [
            'orders' => $orders,
            // The list refreshes itself while a load is still open, so the result shows up without reloading
            'hasOpenApiOrders' => $orders->getCollection()->contains(fn (SimOrder $o) => in_array($o->api_status, SimOrder::API_OPEN_STATES, true)),
            'canManage' => $canManage,
            'stats' => $stats = $this->statistics($base),
            'topPackages' => $canManage ? $this->topPackages($base, $stats['done_count']) : collect(),
            'pendingCount' => $canManage ? SimOrder::where('status', SimOrder::STATUS_PENDING)->count() : 0,
            // Our balance at kontor86 (also shows at a glance whether the API connection works)
            'supplierBalance' => $this->supplierBalance(),
        ]);
    }
}
