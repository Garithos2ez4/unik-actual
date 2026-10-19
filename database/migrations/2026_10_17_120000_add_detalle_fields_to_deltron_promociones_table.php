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
        Schema::table('deltron_promociones', function (Blueprint $table) {
            $table->decimal('precio_promocion', 12, 2)->nullable()->after('sku_descripcion');
            $table->dateTime('fecha_fin')->nullable()->after('precio_promocion');
            $table->json('principal_json')->nullable()->after('fecha_fin');
            $table->json('items_combo_json')->nullable()->after('principal_json');
            $table->json('stock_promocion_json')->nullable()->after('items_combo_json');
            $table->integer('stock_total_promo')->default(0)->after('stock_promocion_json');
            $table->boolean('detalle_cargado')->default(false)->after('stock_total_promo');
            $table->timestamp('detalle_actualizado_at')->nullable()->after('detalle_cargado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deltron_promociones', function (Blueprint $table) {
            $table->dropColumn([
                'precio_promocion',
                'fecha_fin',
                'principal_json',
                'items_combo_json',
                'stock_promocion_json',
                'stock_total_promo',
                'detalle_cargado',
                'detalle_actualizado_at'
            ]);
        });
    }
};
