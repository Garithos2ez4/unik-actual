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
        Schema::table('ProductoPack', function (Blueprint $table) {
            $table->decimal('porcentaje_costo', 5, 2)->default(0)->comment('Porcentaje del costo total del pack asignado a este componente (0-100)');
        });

        Schema::table('DivisionPackDetalle', function (Blueprint $table) {
            $table->decimal('costo_asignado', 10, 2)->nullable()->comment('Costo real asignado a la nueva unidad generada tras la división');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ProductoPack', function (Blueprint $table) {
            $table->dropColumn('porcentaje_costo');
        });

        Schema::table('DivisionPackDetalle', function (Blueprint $table) {
            $table->dropColumn('costo_asignado');
        });
    }
};
