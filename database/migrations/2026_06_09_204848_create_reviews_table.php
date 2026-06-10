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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id('idReview');
            $table->integer('idCliente');
            $table->integer('idProducto')->nullable(); // Can be null for general store reviews
            $table->integer('calificacion');
            $table->text('comentario');
            $table->string('imagen_setup')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('estado')->default(1);
            $table->timestamps();

            $table->foreign('idCliente')->references('idCliente')->on('Cliente')->onDelete('cascade');
            $table->foreign('idProducto')->references('idProducto')->on('Producto')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
