<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes_persona', function (Blueprint $table) {
            $table->string('direccion', 200)->nullable()->after('email');
            $table->date('fecha_cumpleanos')->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('clientes_persona', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'fecha_cumpleanos']);
        });
    }
};
