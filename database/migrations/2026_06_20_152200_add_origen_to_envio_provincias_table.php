<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envio_provincia_detalles', function (Blueprint $table) {
            $table->string('origen', 30)->default('INTERNO')->after('ref');
        });
    }

    public function down(): void
    {
        Schema::table('envio_provincia_detalles', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
