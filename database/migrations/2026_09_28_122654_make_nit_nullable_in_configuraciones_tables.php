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
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->string('nit', 30)->nullable()->change();
        });

        Schema::table('configuracion_liquidaciones', function (Blueprint $table) {
            $table->string('nit', 30)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->string('nit', 30)->nullable(false)->change();
        });

        Schema::table('configuracion_liquidaciones', function (Blueprint $table) {
            $table->string('nit', 30)->nullable(false)->change();
        });
    }
};
