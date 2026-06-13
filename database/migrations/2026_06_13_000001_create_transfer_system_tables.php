<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commission_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->decimal('min_amount', 15, 3);
            $table->decimal('max_amount', 15, 3);
            $table->enum('commission_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('commission_value', 15, 3);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 15, 5);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->decimal('amount', 15, 3);
            $table->string('currency', 3)->default('TRY');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('request_id')->nullable()->constrained('transfer_requests')->onDelete('set null');
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->decimal('source_amount', 15, 3);
            $table->string('source_currency', 3)->default('TRY');
            $table->string('target_currency', 3)->default('EGP');
            $table->decimal('exchange_rate', 15, 5);
            $table->decimal('received_amount', 15, 3);
            $table->decimal('commission', 15, 3);
            $table->decimal('net_amount', 15, 3);
            $table->string('secret_code', 5);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('code');
            $table->string('type')->default('login_2fa');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('transfer_requests');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('commission_tiers');
    }
};
