<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Venta', function (Blueprint $table) {
            $indexesFound = collect(Schema::getIndexes('Venta'))->pluck('name')->toArray();

            if (!in_array('idx_venta_numeroorden', $indexesFound)) {
                $table->index('numeroOrden', 'idx_venta_numeroorden');
            }
        });

        Schema::table('EgresoProducto', function (Blueprint $table) {
            $indexesFound = collect(Schema::getIndexes('EgresoProducto'))->pluck('name')->toArray();

            if (!in_array('idx_egresoproducto_numeroorden', $indexesFound)) {
                $table->index('numeroOrden', 'idx_egresoproducto_numeroorden');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Venta', function (Blueprint $table) {
            $table->dropIndex('idx_venta_numeroorden');
        });

        Schema::table('EgresoProducto', function (Blueprint $table) {
            $table->dropIndex('idx_egresoproducto_numeroorden');
        });
    }
};
