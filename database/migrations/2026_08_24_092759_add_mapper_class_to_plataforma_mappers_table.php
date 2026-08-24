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
        Schema::table('plataforma_mappers', function (Blueprint $table) {
            $table->string('mapper_class')->nullable()->after('tipo_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plataforma_mappers', function (Blueprint $table) {
            $table->dropColumn('mapper_class');
        });
    }
};
