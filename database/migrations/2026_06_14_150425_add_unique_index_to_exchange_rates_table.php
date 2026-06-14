<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Remove duplicates before applying unique constraint
        $duplicates = DB::table('exchange_rates')
            ->select('from_currency', 'to_currency', DB::raw('MAX(id) as max_id'))
            ->groupBy('from_currency', 'to_currency')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('exchange_rates')
                ->where('from_currency', $duplicate->from_currency)
                ->where('to_currency', $duplicate->to_currency)
                ->where('id', '!=', $duplicate->max_id)
                ->delete();
        }

        // 2. Add the unique index
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->unique(['from_currency', 'to_currency'], 'exchange_rates_from_to_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropUnique('exchange_rates_from_to_unique');
        });
    }
};
