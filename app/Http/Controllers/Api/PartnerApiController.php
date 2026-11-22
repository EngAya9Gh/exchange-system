<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SimOrderException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticatePartnerApi;
use App\Livewire\Sim\BuyPackage;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Models\User;
use App\Services\SimOrderService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Partner API v1: other systems order SIM / internet packages with their account's balance.
 * Same rules as the web screen (price, balance + credit limit, supplier routing, refunds).
 * Documentation: /api-docs
 */
class PartnerApiController extends Controller
{
    /** GET /api/v1/balance */
    public function balance(Request $request): JsonResponse
    {
        return response()->json(['ok' => true, 'balance' => $this->balanceOf($request->user())]);
    }

    /** GET /api/v1/packages */
    public function packages(): JsonResponse
    {
        $packages = SimPackage::active()->ordered()->get()->map(fn (SimPackage $p) => [
            'id' => $p->id,
            'name_ar' => $p->name_ar,
            'name_tr' => $p->name_tr,
            'region' => $p->region === SimPackage::REGION_EUROPE ? 'europe' : 'turkey',
            'type' => $p->sim_type,
            'network' => $p->network_name,
            'data_gb' => $p->data_gb,
            'validity_days' => $p->validity_days,
            'price' => (float) $p->price,
            'currency' => 'TRY',
            'phone_format' => $p->isEsim() ? '5XXXXXXXXX' : '50XXXXXXXX',
        ])->values();

        return response()->json(['ok' => true, 'packages' => $packages]);
    }

    /** POST /api/v1/orders */
    public function createOrder(Request $request, SimOrderService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'package_id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:30'],
            'reference' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails()) {
            return AuthenticatePartnerApi::error('validation_error', 'Some fields are missing or invalid.', 422, ['fields' => $validator->errors()->toArray()]);
        }

        $user = $request->user();
        $reference = (string) $request->input('reference');

        // Same reference again → the same order, never a second charge
        if ($existing = $this->findByReference($user, $reference)) {
            return $this->orderResponse($existing, $user, 200, true);
        }

        $package = SimPackage::active()->find((int) $request->input('package_id'));
        if (!$package) {
            return AuthenticatePartnerApi::error('package_unavailable', 'This package does not exist or is not on sale.', 422);
        }

        $phone = $this->normalizePhone($package, (string) $request->input('phone'));
        if ($phone === null) {
            return AuthenticatePartnerApi::error('invalid_phone', $package->isEsim()
                ? 'eSIM packages need a Turkish mobile number: 10 digits starting with 5 (5XXXXXXXXX).'
                : 'Physical SIM packages need the line number: 10 digits starting with 50 (50XXXXXXXX).', 422);
        }

        try {
            $order = $service->placeOrder(
                $user,
                $package->id,
                $phone,
                $request->input('customer_name') ? trim((string) $request->input('customer_name')) : null,
                $request->input('notes') ? trim((string) $request->input('notes')) : null,
                $reference
            );
        } catch (SimOrderException $e) {
            return AuthenticatePartnerApi::error($e->errorCode, $e->getMessage(), 422);
        } catch (QueryException $e) {
            // Two identical requests at the same moment: the unique reference let only one through
            if ($existing = $this->findByReference($user, $reference)) {
                return $this->orderResponse($existing, $user, 200, true);
            }
            Log::error('Partner API order failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            return AuthenticatePartnerApi::error('server_error', 'The order could not be created, please try again.', 500);
        }

        return $this->orderResponse($order->fresh(), $user, 201);
    }

    /** GET /api/v1/orders/{id}  (our order number, or your reference) */
    public function showOrder(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $order = SimOrder::where('user_id', $user->id)
            ->where(fn ($q) => $q->where('order_number', $id)->orWhere('client_reference', $id))
            ->first();

        if (!$order) {
            return AuthenticatePartnerApi::error('order_not_found', 'No order with this number or reference on your account.', 404);
        }

        return response()->json(['ok' => true, 'order' => $order->toPartnerArray()]);
    }

    protected function findByReference(User $user, string $reference): ?SimOrder
    {
        return SimOrder::where('user_id', $user->id)->where('client_reference', $reference)->first();
    }

    protected function orderResponse(SimOrder $order, User $user, int $status, bool $duplicate = false): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'duplicate' => $duplicate,
            'order' => $order->toPartnerArray(),
            'balance' => $this->balanceOf($user->fresh()),
        ], $status);
    }

    /** Physical: 50XXXXXXXX (also 05…, +90…, or the last 8 digits). eSIM: any Turkish mobile 5XXXXXXXXX. */
    protected function normalizePhone(SimPackage $package, string $input): ?string
    {
        if ($package->isEsim()) {
            $digits = BuyPackage::normalizePhone($input);

            return preg_match('/^5\d{9}$/', $digits) ? $digits : null;
        }

        $lastEight = BuyPackage::lastEightDigits($input);

        return $lastEight === null ? null : BuyPackage::PHONE_PREFIX . $lastEight;
    }

    protected function balanceOf(User $user): array
    {
        return [
            'balance' => round((float) $user->balance, 2),
            'credit_limit' => $user->has_unlimited_balance ? null : round((float) $user->balance_limit, 2),
            'unlimited' => (bool) $user->has_unlimited_balance,
            'available' => $user->has_unlimited_balance ? null : round((float) $user->balance + (float) $user->balance_limit, 2),
            'currency' => 'TRY',
        ];
    }
}
