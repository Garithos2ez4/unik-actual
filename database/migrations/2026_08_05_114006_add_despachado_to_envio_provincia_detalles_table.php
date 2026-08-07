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
        Schema::table('envio_provincia_detalles', function (Blueprint $table) {
            $table->boolean('despachado')->default(false)->after('origen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envio_provincia_detalles', function (Blueprint $table) {
            $table->dropColumn('despachado');
        });
    }
};
