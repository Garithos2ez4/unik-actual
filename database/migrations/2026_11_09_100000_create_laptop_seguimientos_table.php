<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laptop_seguimientos')) {
            Schema::create('laptop_seguimientos', function (Blueprint $table) {
                $table->id('idLaptopSeguimiento');
                $table->integer('idProducto')->nullable()->index();
                $table->integer('idMarca')->nullable()->index();
                $table->string('marca_nombre', 60)->default('OTRAS')->index();
                $table->string('plataforma_cpu', 40)->default('INTEL CORE');
                $table->string('gama', 60)->default('core i7 / CORE 7 / ultra 7');
                $table->integer('idUser')->nullable()->index();
                $table->string('responsable_texto', 60)->nullable();
                $table->string('modelo', 180);
                $table->string('serie', 120)->nullable();
                $table->string('tamano', 40)->nullable();
                $table->string('frecuencia', 40)->nullable();
                $table->string('procesador', 120)->nullable();
                $table->string('ram', 40)->nullable();
                $table->string('disco', 40)->nullable();
                $table->string('video', 80)->nullable();
                $table->string('part_number', 120)->nullable()->index();
                $table->string('stock_texto', 50)->nullable();
                $table->string('stock_unik', 50)->nullable();
                $table->date('fecha_revision')->nullable()->index();
                $table->string('estado_fila', 30)->default('NORMAL')->index();
                $table->json('proveedores_data')->nullable();
                $table->json('cuentas_ml_data')->nullable();
                $table->boolean('oculto')->default(false)->index();
                $table->integer('orden')->default(0);
                $table->text('observacion')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laptop_seguimientos');
    }
};

