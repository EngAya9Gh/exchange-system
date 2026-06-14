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
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->change();
            $table->string('sender_phone')->nullable()->change();
            $table->string('destination')->default('جميع المحافظات - فودافون مباشر')->after('recipient_phone');
            $table->text('address')->nullable()->after('destination');
            $table->text('notes')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('sender_name')->nullable(false)->change();
            $table->string('sender_phone')->nullable(false)->change();
            $table->dropColumn(['destination', 'address', 'notes']);
        });
    }
};
