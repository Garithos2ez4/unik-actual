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
        // Migrate data
        $detalles = DB::table('DetalleProducto')->where('en_liquidacion', true)->get();
        foreach ($detalles as $detalle) {
            DB::table('Liquidacion')->insert([
                'idProducto' => $detalle->idProducto,
                'precio_liquidacion' => $detalle->precio_liquidacion,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('DetalleProducto', function (Blueprint $table) {
            $table->dropColumn(['en_liquidacion', 'precio_liquidacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DetalleProducto', function (Blueprint $table) {
            $table->boolean('en_liquidacion')->default(false)->after('precio_pase');
            $table->decimal('precio_liquidacion', 10, 2)->nullable()->after('en_liquidacion');
        });

        // Migrate back
        $liquidaciones = DB::table('Liquidacion')->get();
        foreach ($liquidaciones as $liq) {
            DB::table('DetalleProducto')
                ->where('idProducto', $liq->idProducto)
                ->update([
                    'en_liquidacion' => true,
                    'precio_liquidacion' => $liq->precio_liquidacion
                ]);
        }
    }
};
