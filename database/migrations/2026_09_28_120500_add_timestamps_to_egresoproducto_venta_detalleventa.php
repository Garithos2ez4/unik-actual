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
        if (Schema::hasTable('EgresoProducto')) {
            Schema::table('EgresoProducto', function (Blueprint $table) {
                if (Schema::hasColumn('EgresoProducto', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });

            Schema::table('EgresoProducto', function (Blueprint $table) {
                $table->dateTime('created_at')->nullable()->after('fechaDespacho');
                $table->dateTime('updated_at')->nullable()->after('created_at');
            });

            // Backfill existing rows with valid dates between 2020 and 2030
            DB::statement("UPDATE EgresoProducto 
                SET created_at = CASE 
                    WHEN fechaDespacho BETWEEN '2020-01-01' AND '2030-12-31' THEN fechaDespacho 
                    WHEN fechaCompra BETWEEN '2020-01-01' AND '2030-12-31' THEN fechaCompra 
                    ELSE NOW() 
                END, 
                updated_at = CASE 
                    WHEN fechaDespacho BETWEEN '2020-01-01' AND '2030-12-31' THEN fechaDespacho 
                    WHEN fechaCompra BETWEEN '2020-01-01' AND '2030-12-31' THEN fechaCompra 
                    ELSE NOW() 
                END 
                WHERE created_at IS NULL");
        }

        if (Schema::hasTable('Venta')) {
            Schema::table('Venta', function (Blueprint $table) {
                if (Schema::hasColumn('Venta', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });

            Schema::table('Venta', function (Blueprint $table) {
                $table->dateTime('created_at')->nullable()->after('observacion');
                $table->dateTime('updated_at')->nullable()->after('created_at');
            });

            // Backfill existing rows
            DB::statement("UPDATE Venta 
                SET created_at = CASE 
                    WHEN fechaVenta BETWEEN '2020-01-01' AND '2030-12-31' THEN fechaVenta 
                    ELSE NOW() 
                END, 
                updated_at = CASE 
                    WHEN fechaVenta BETWEEN '2020-01-01' AND '2030-12-31' THEN fechaVenta 
                    ELSE NOW() 
                END 
                WHERE created_at IS NULL");
        }

        if (Schema::hasTable('DetalleVenta')) {
            Schema::table('DetalleVenta', function (Blueprint $table) {
                if (Schema::hasColumn('DetalleVenta', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });

            Schema::table('DetalleVenta', function (Blueprint $table) {
                $table->dateTime('created_at')->nullable()->after('estado');
                $table->dateTime('updated_at')->nullable()->after('created_at');
            });

            // Backfill existing rows from Venta or EgresoProducto
            DB::statement("UPDATE DetalleVenta d 
                LEFT JOIN Venta v ON d.idVenta = v.idVenta 
                LEFT JOIN EgresoProducto e ON d.idEgreso = e.idEgreso 
                SET d.created_at = CASE 
                    WHEN v.created_at BETWEEN '2020-01-01' AND '2030-12-31' THEN v.created_at 
                    WHEN v.fechaVenta BETWEEN '2020-01-01' AND '2030-12-31' THEN v.fechaVenta 
                    WHEN e.created_at BETWEEN '2020-01-01' AND '2030-12-31' THEN e.created_at 
                    WHEN e.fechaDespacho BETWEEN '2020-01-01' AND '2030-12-31' THEN e.fechaDespacho 
                    ELSE NOW() 
                END, 
                d.updated_at = CASE 
                    WHEN v.updated_at BETWEEN '2020-01-01' AND '2030-12-31' THEN v.updated_at 
                    WHEN v.fechaVenta BETWEEN '2020-01-01' AND '2030-12-31' THEN v.fechaVenta 
                    WHEN e.updated_at BETWEEN '2020-01-01' AND '2030-12-31' THEN e.updated_at 
                    WHEN e.fechaDespacho BETWEEN '2020-01-01' AND '2030-12-31' THEN e.fechaDespacho 
                    ELSE NOW() 
                END 
                WHERE d.created_at IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('EgresoProducto')) {
            Schema::table('EgresoProducto', function (Blueprint $table) {
                if (Schema::hasColumn('EgresoProducto', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });
        }

        if (Schema::hasTable('Venta')) {
            Schema::table('Venta', function (Blueprint $table) {
                if (Schema::hasColumn('Venta', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });
        }

        if (Schema::hasTable('DetalleVenta')) {
            Schema::table('DetalleVenta', function (Blueprint $table) {
                if (Schema::hasColumn('DetalleVenta', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });
        }
    }
};
