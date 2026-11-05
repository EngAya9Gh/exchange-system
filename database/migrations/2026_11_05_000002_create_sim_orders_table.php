<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sim_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sim_package_id')->nullable()->constrained('sim_packages')->nullOnDelete();

            // Snapshot of the package at order time (prices and names can change later)
            $table->string('package_name_ar');
            $table->string('package_name_tr');
            $table->string('region', 20);
            $table->string('sim_type', 20);
            $table->decimal('price', 15, 2);
            // What was actually taken from the user's balance (0 for Super Admin orders)
            $table->decimal('charged_amount', 15, 2)->default(0);

            $table->string('phone_number', 30);
            $table->string('customer_name')->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 20)->default('pending'); // pending | completed | rejected
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_orders');
    }
};
