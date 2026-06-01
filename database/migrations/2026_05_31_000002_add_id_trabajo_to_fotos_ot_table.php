<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fotos_ot', function (Blueprint $table) {
            $table->foreignId('id_trabajo')
                  ->nullable()
                  ->after('id_ot')
                  ->constrained('trabajo_tecnico')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fotos_ot', function (Blueprint $table) {
            $table->dropForeign(['id_trabajo']);
            $table->dropColumn('id_trabajo');
        });
    }
};
