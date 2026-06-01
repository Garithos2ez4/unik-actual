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
        Schema::create('solicitudes_envio', function (Blueprint $table) {
            $table->id('idSolicitud');
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('idUser'); // Quién generó el link
            $table->enum('estado', ['PENDIENTE', 'PROCESADO'])->default('PENDIENTE');
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
            
            // Relación opcional si quieres forzar consistencia
            // $table->foreign('idUser')->references('idUser')->on('Usuario')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_envio');
    }
};
