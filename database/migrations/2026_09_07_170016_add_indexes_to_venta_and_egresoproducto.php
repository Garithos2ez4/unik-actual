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
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('Venta');

            if (!array_key_exists('idx_venta_numeroorden', $indexesFound)) {
                $table->index('numeroOrden', 'idx_venta_numeroorden');
            }
        });

        Schema::table('EgresoProducto', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('EgresoProducto');

            if (!array_key_exists('idx_egresoproducto_numeroorden', $indexesFound)) {
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
