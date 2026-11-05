<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sim_packages', function (Blueprint $table) {
            $table->id();
            $table->string('region', 20);    // local | europe
            $table->string('sim_type', 20);  // physical | esim
            $table->unsignedInteger('data_gb');
            $table->unsignedInteger('validity_days');
            $table->decimal('price', 15, 2);
            $table->string('name_ar');
            $table->string('name_tr');
            $table->text('description_ar')->nullable();
            $table->text('description_tr')->nullable();
            // Package name as it appears on the supplier panel, so the admin can find it quickly
            $table->string('supplier_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'region', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_packages');
    }
};
