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
        Schema::create('falabella_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('order_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('status')->nullable();
            $table->json('statuses')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('shipping_type')->nullable();
            $table->string('delivery_info')->nullable();
            $table->integer('items_count')->default(0);
            $table->dateTime('created_at_falabella')->nullable();
            $table->dateTime('updated_at_falabella')->nullable();
            $table->dateTime('promised_shipping_time')->nullable();
            $table->string('shipping_city')->nullable();
            $table->text('shipping_address')->nullable();
            $table->json('payload')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->date('sync_date')->nullable();
            $table->timestamps();
        });

        Schema::create('falabella_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_item_id')->unique();
            $table->string('order_id');
            $table->string('order_number')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('falabella_sku')->nullable();
            $table->string('shop_sku')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->string('tracking_code')->nullable();
            $table->string('package_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('falabella_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('falabella_order_items');
        Schema::dropIfExists('falabella_orders');
    }
};
