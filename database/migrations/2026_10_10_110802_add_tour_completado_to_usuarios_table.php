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
        Schema::table('Usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('Usuario', 'tour_completado')) {
                $table->boolean('tour_completado')->default(false)->after('idUser');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Usuario', function (Blueprint $table) {
            if (Schema::hasColumn('Usuario', 'tour_completado')) {
                $table->dropColumn('tour_completado');
            }
        });
    }
};
