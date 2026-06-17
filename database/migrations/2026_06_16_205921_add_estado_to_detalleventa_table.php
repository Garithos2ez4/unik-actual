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
        Schema::table('DetalleVenta', function (Blueprint $table) {
            $table->string('estado', 20)->default('COMPLETADO')->after('origenPrecio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DetalleVenta', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
