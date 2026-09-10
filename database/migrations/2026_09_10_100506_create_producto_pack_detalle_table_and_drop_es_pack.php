<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ProductoPackDetalle', function (Blueprint $table) {
            $table->id('idDetalle');
            $table->integer('idGrupoProducto');
            // Eliminado $table->timestamps() ya que la DB parece no usarlos por defecto en catálogos
            
            // Si GrupoProducto es innoDB, podemos agregar foreign:
            // $table->foreign('idGrupoProducto')->references('idGrupoProducto')->on('GrupoProducto')->onDelete('cascade');
        });

        // Migrar los grupos existentes que tenían es_pack = true
        if (Schema::hasColumn('GrupoProducto', 'es_pack')) {
            $packs = DB::table('GrupoProducto')->where('es_pack', true)->pluck('idGrupoProducto');
            foreach ($packs as $id) {
                DB::table('ProductoPackDetalle')->insert(['idGrupoProducto' => $id]);
            }
            
            Schema::table('GrupoProducto', function (Blueprint $table) {
                $table->dropColumn('es_pack');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('GrupoProducto', 'es_pack')) {
            Schema::table('GrupoProducto', function (Blueprint $table) {
                $table->boolean('es_pack')->default(false)->after('slugGrupo');
            });
            
            // Revertir datos
            $packs = DB::table('ProductoPackDetalle')->pluck('idGrupoProducto');
            if ($packs->count() > 0) {
                DB::table('GrupoProducto')->whereIn('idGrupoProducto', $packs)->update(['es_pack' => true]);
            }
        }
        
        Schema::dropIfExists('ProductoPackDetalle');
    }
};
