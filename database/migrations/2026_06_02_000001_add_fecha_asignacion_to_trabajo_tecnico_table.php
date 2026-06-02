<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajo_tecnico', function (Blueprint $table) {
            $table->date('fecha_asignacion')->nullable()->after('especialidad');
        });

        DB::statement('UPDATE trabajo_tecnico SET fecha_asignacion = DATE(created_at)');
    }

    public function down(): void
    {
        Schema::table('trabajo_tecnico', function (Blueprint $table) {
            $table->dropColumn('fecha_asignacion');
        });
    }
};
