<?php

use App\Models\SimPackage;
use Illuminate\Database\Migrations\Migration;

/**
 * Links every package that no supplier key reaches (e.g. the three the teacher added by hand on 2026-11-24)
 * to the single supplier product with the same specs: region, SIM type, network, GB and days
 * (config/sim_supplier_products.php; a physical Turkey SIM is a Tele CARD "50" line).
 *   5 GB · 30 days physical → kontor86 26240 · 30 GB · 30 days physical → kontor86 26257.
 * Packages with no product of the same specs (e.g. 100 GB · 180 days) stay manual.
 * Packages that already have a key are never touched. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // deploy.sh runs migrate before rebuilding the config cache: read the product list from its file
        config(['sim_supplier_products' => require config_path('sim_supplier_products.php')]);

        foreach (SimPackage::all() as $package) {
            if (!$package->isApiReady()) {
                $package->applySuggestedLinks();
            }
        }
    }

    public function down(): void
    {
        // Links are kept (they can be changed in «إدارة الباقات والأسعار»).
    }
};
