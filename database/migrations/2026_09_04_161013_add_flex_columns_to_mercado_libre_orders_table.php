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
        Schema::table('ml_orders', function (Blueprint $table) {
            $table->string('shipping_substatus')->nullable()->after('shipping_status');
            $table->unsignedBigInteger('driver_id')->nullable()->after('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ml_orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_substatus', 'driver_id']);
        });
    }
};
