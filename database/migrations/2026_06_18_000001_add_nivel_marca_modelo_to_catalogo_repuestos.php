<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalogo_repuestos', function (Blueprint $table) {
            $table->unsignedTinyInteger('nivel')->default(1)->after('id');
            $table->foreignId('id_marca')
                  ->nullable()->after('nivel')
                  ->constrained('marcas_vehiculo')->onDelete('cascade');
            $table->foreignId('id_modelo')
                  ->nullable()->after('id_marca')
                  ->constrained('modelos_vehiculo')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_repuestos', function (Blueprint $table) {
            $table->dropForeign(['id_marca']);
            $table->dropForeign(['id_modelo']);
            $table->dropColumn(['nivel', 'id_marca', 'id_modelo']);
        });
    }
};
