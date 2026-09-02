<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Credenciales OAuth por cuenta ML
        Schema::create('ml_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('seller_id')->unique();
            $table->string('seller_nickname')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->timestamps();
        });

        // Órdenes de Mercado Libre
        Schema::create('ml_orders', function (Blueprint $table) {
            $table->id();
            $table->string('ml_order_id')->unique();
            $table->string('seller_id')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_nickname')->nullable();
            $table->string('status')->nullable();         // paid, cancelled, etc.
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('currency', 5)->nullable();
            // Envío
            $table->string('shipping_id')->nullable();
            $table->string('logistic_type')->nullable();  // flex, drop_off, fulfillment, custom
            $table->string('logistic_label')->nullable(); // Etiqueta legible: Flex, Urbano, etc.
            $table->string('shipping_status')->nullable();
            $table->string('shipping_mode')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->dateTime('date_handled')->nullable();
            $table->dateTime('date_shipped')->nullable();
            $table->dateTime('date_delivered')->nullable();
            // Devoluciones
            $table->string('return_status')->nullable();
            $table->string('return_reason')->nullable();
            // Meta
            $table->integer('items_count')->default(0);
            $table->json('payload')->nullable();
            $table->dateTime('created_at_ml')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->date('sync_date')->nullable();
            $table->timestamps();
        });

        // Ítems de cada orden ML
        Schema::create('ml_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('ml_item_id')->unique();
            $table->string('ml_order_id');
            $table->string('seller_sku')->nullable();
            $table->string('ml_sku')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('ml_order_id')->references('ml_order_id')->on('ml_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_order_items');
        Schema::dropIfExists('ml_orders');
        Schema::dropIfExists('ml_credentials');
    }
};
