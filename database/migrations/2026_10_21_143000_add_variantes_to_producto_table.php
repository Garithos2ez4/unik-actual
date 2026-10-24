<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistema de variantes (Padre-Hijo).
     *
     * Producto (columnas de filtrado, consultadas en cada listado):
     * - Padre:    es_padre = 1, idProductoPadre = NULL (contenedor, no vendible).
     * - Variante: es_padre = 0, idProductoPadre = id del padre (vendible, stock propio).
     * - Simple:   es_padre = 0, idProductoPadre = NULL.
     *
     * DetalleProducto (atributos descriptivos, solo se leen en la ficha/selector):
     * - tipo_variante, valor_variante, codigo_color_hex.
     */
    public function up(): void
    {
        Schema::table('Producto', function (Blueprint $table) {
            if (!Schema::hasColumn('Producto', 'idProductoPadre')) {
                $table->integer('idProductoPadre')->nullable()->default(null)->after('idProducto');
            }
            if (!Schema::hasColumn('Producto', 'es_padre')) {
                $table->boolean('es_padre')->default(false)->after('idProductoPadre');
            }
        });

        Schema::table('Producto', function (Blueprint $table) {
            $table->index(['idProductoPadre', 'es_padre', 'estadoProductoWeb'], 'idx_padre_web');
        });

        Schema::table('DetalleProducto', function (Blueprint $table) {
            if (!Schema::hasColumn('DetalleProducto', 'tipo_variante')) {
                $table->string('tipo_variante', 50)->nullable()->after('precio_pase');
            }
            if (!Schema::hasColumn('DetalleProducto', 'valor_variante')) {
                $table->string('valor_variante', 100)->nullable()->after('tipo_variante');
            }
            if (!Schema::hasColumn('DetalleProducto', 'codigo_color_hex')) {
                $table->string('codigo_color_hex', 20)->nullable()->after('valor_variante');
            }
        });
    }

    public function down(): void
    {
        Schema::table('DetalleProducto', function (Blueprint $table) {
            $table->dropColumn(['tipo_variante', 'valor_variante', 'codigo_color_hex']);
        });

        Schema::table('Producto', function (Blueprint $table) {
            $table->dropIndex('idx_padre_web');
        });

        Schema::table('Producto', function (Blueprint $table) {
            $table->dropColumn(['idProductoPadre', 'es_padre']);
        });
    }
};
