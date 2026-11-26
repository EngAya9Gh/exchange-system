<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «⚡ سريع · Hızlı» packages: kontor86's «HIZLI FİZİKSEL» products on Tele CARD lines (Kontor Fiyat Ayarları,
 * 2026-11-25: 5 GB 7036449 · 10 GB 6987918 · 20 GB 2652690 · 50 GB 2285493 · 100 GB 2205285, all 30 days).
 *  1. sim_packages.is_fast: the card gets its own colors and the word «سريع · Hızlı»; such packages link only to
 *     HIZLI products, and the other packages never do.
 *  2. The 50 GB «باقة سريعة» the teacher added by hand (network Türk Telekom, linked on save to the ordinary
 *     products, which cost more than its price) becomes the HIZLI 50 GB: network Tele CARD, kontor86 key 2285493,
 *     no telekomtl key (telekomtl has no HIZLI product). Its names, price and on/off state are kept.
 *  3. Every other HIZLI size is added HIDDEN, linked and ready: the owner chose to let the teacher set the
 *     prices and switch them on. The starting price is the one of the ordinary package of the same size.
 * Idempotent.
 */
return new class extends Migration
{
    private const HIZLI = [5 => '7036449', 10 => '6987918', 20 => '2652690', 50 => '2285493', 100 => '2205285'];

    public function up(): void
    {
        if (!Schema::hasColumn('sim_packages', 'is_fast')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->boolean('is_fast')->default(false)->after('badge');
            });
        }

        $key = fn (int $gb) => [
            'network_name' => 'Tele CARD',
            'is_fast' => true,
            'supplier_code' => "HIZLI FİZİKSEL {$gb}GB",
            'api_operator' => 'Callback',
            'api_tip' => 'TeleCard',
            'api_code' => self::HIZLI[$gb],
            'telekomtl_operator' => null,
            'telekomtl_tip' => null,
            'telekomtl_code' => null,
            'preferred_supplier' => null,
        ];

        // 2. The teacher's 50 GB «باقة سريعة»
        $teacherCards = DB::table('sim_packages')
            ->whereNull('deleted_at')
            ->where('region', 'local')
            ->where('sim_type', 'physical')
            ->where('data_gb', 50)
            ->where('validity_days', 30)
            ->where('is_fast', false)
            ->where(fn ($q) => $q->where('name_ar', 'like', '%سريع%')
                ->orWhere('description_ar', 'like', '%سريع%')
                ->orWhere('name_tr', 'like', '%hızlı%')
                ->orWhere('name_tr', 'like', '%hizli%'))
            ->pluck('id');
        foreach ($teacherCards as $id) {
            DB::table('sim_packages')->where('id', $id)->update($key(50) + ['updated_at' => now()]);
        }

        // 3. The other sizes, hidden until the teacher prices them
        $sort = (int) DB::table('sim_packages')->max('sort_order');
        foreach (self::HIZLI as $gb => $code) {
            $exists = DB::table('sim_packages')
                ->whereNull('deleted_at')
                ->where('is_fast', true)
                ->where('region', 'local')
                ->where('sim_type', 'physical')
                ->where('data_gb', $gb)
                ->where('validity_days', 30)
                ->exists();
            if ($exists) {
                continue;
            }

            $ordinaryPrice = DB::table('sim_packages')
                ->whereNull('deleted_at')
                ->where('is_fast', false)
                ->where('region', 'local')
                ->where('sim_type', 'physical')
                ->where('data_gb', $gb)
                ->where('validity_days', 30)
                ->orderBy('sort_order')
                ->value('price');

            DB::table('sim_packages')->insert($key($gb) + [
                'region' => 'local',
                'sim_type' => 'physical',
                'data_gb' => $gb,
                'validity_days' => 30,
                'price' => $ordinaryPrice ?? 0,
                'name_ar' => "باقة تركيا سريعة {$gb} جيجا - 30 يوم (فيزيائية)",
                'name_tr' => "Türkiye Hızlı {$gb} GB - 30 Gün (Fiziksel)",
                'is_active' => false,
                'sort_order' => ++$sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Packages are kept (they can be edited or deleted in «إدارة الباقات والأسعار»).
        if (Schema::hasColumn('sim_packages', 'is_fast')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->dropColumn('is_fast');
            });
        }
    }
};
