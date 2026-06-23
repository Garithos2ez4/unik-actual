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
        Schema::create('UbicacionAlmacen', function (Blueprint $table) {
            $table->id('idUbicacion'); // Auto-increment primary key
            $table->integer('idAlmacen')->index(); // Matches Almacen.idAlmacen which is int(11)
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            // Clave foránea manual si está soportada, o simplemente relación lógica
            // (La tabla Almacen podría no usar InnoDB o tener otro collation, omitimos FK estricta a menos que estemos seguros, 
            // pero añadiremos la restricción por si acaso. Si falla, el catch lo revelará).
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('UbicacionAlmacen');
    }
};
