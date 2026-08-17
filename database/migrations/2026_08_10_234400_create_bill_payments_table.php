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
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('kurum_id');
            $table->string('abone_no');
            $table->string('fatura_no')->nullable();
            $table->decimal('amount', 15, 3);
            $table->decimal('api_cost', 15, 3)->default(0);
            $table->decimal('commission', 15, 3)->default(0);
            $table->decimal('total_deducted', 15, 3)->default(0);
            $table->string('tahsilat_api_islem_id')->unique();
            $table->enum('api_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('api_status_message')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
