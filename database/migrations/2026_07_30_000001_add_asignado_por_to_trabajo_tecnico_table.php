<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajo_tecnico', function (Blueprint $table) {
            // Usuario que asignó el técnico. Nullable: las asignaciones ya
            // existentes no tienen forma de recuperar este dato.
            $table->foreignId('asignado_por')->nullable()->after('fecha_asignacion')
                ->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('trabajo_tecnico', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asignado_por');
        });
    }
};
