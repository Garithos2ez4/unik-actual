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
        Schema::create('inventario_proveedor_skus', function (Blueprint $table) {
            $table->id();
            $table->integer('idInventarioProveedor')->index();
            $table->integer('idProveedor')->default(1)->index();
            $table->string('sku_proveedor', 100)->index();
            $table->string('mini_codigo', 50)->nullable()->index();
            $table->string('part_number', 100)->nullable()->index();
            $table->json('datos_extra')->nullable();
            $table->timestamps();

            $table->unique(['idInventarioProveedor', 'idProveedor'], 'inv_prov_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_proveedor_skus');
    }
};
