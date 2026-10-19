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
        if (!Schema::hasTable('deltron_promociones')) {
            Schema::create('deltron_promociones', function (Blueprint $table) {
                $table->id();
                $table->string('promocion_id')->index();
                $table->text('promocion_descripcion');
                $table->string('sku')->nullable()->index();
                $table->string('sku_descripcion')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deltron_promociones');
    }
};
