<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El teléfono a veces guarda dos números ("300... / 320..."), así que
        // se amplía de 20 a 25 caracteres para que quepan.
        Schema::table('clientes_persona', function (Blueprint $table) {
            $table->string('telefono', 25)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes_persona', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->change();
        });
    }
};
