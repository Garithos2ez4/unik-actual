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
        Schema::table('DetalleProducto', function (Blueprint $table) {
            $table->boolean('en_liquidacion')->default(false)->after('precio_pase');
            $table->decimal('precio_liquidacion', 10, 2)->nullable()->after('en_liquidacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DetalleProducto', function (Blueprint $table) {
            $table->dropColumn(['en_liquidacion', 'precio_liquidacion']);
        });
    }
};
