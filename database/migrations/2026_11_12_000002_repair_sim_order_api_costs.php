<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repairs automatic SIM orders whose supplier cost was misread.
 * The old reader took the LAST number of "OK|1|Açıklama|Maliyet|…", so when the live API added a
 * number after the cost (our remaining balance) it stored the balance as the cost (e.g. 10,545 ₺ for a
 * 250 ₺ package) and kept "Açıklama|Maliyet" as the message.
 *  - message ends with "|<number>" → that number is the real cost; the message is cleaned.
 *  - otherwise a cost above twice the sale price cannot be real → cleared (shown as unknown).
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orders = DB::table('sim_orders')
            ->whereNotNull('api_cost')
            ->get(['id', 'price', 'api_cost', 'api_message']);

        foreach ($orders as $order) {
            $price = (float) $order->price;
            $message = (string) $order->api_message;
            $changes = null;

            if (preg_match('/^(.*?)\s*\|\s*(\d+(?:\.\d+)?)\s*$/s', $message, $m) && (float) $m[2] > 0 && (float) $m[2] <= $price * 2) {
                $changes = ['api_cost' => (float) $m[2], 'api_message' => trim($m[1]) !== '' ? trim($m[1]) : null];
            } elseif ((float) $order->api_cost > $price * 2) {
                $changes = ['api_cost' => null];
            }

            if ($changes !== null) {
                DB::table('sim_orders')->where('id', $order->id)->update($changes + ['updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // The misread values are not worth restoring.
    }
};
