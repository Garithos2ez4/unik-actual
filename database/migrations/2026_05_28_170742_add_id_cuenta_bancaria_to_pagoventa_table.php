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
        Schema::table('PagoVenta', function (Blueprint $table) {
            $table->unsignedBigInteger('idCuentaBancaria')->nullable()->after('idMetodoPago');
            
            // Si quieres añadir la llave foránea:
            // $table->foreign('idCuentaBancaria')->references('idCuentaBancaria')->on('CuentasTransferencia')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('PagoVenta', function (Blueprint $table) {
            $table->dropColumn('idCuentaBancaria');
        });
    }
};
