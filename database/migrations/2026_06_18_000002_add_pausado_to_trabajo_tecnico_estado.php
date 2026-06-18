<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar PAUSADO al enum de estado (detener/retomar el trabajo del técnico)
        DB::statement("ALTER TABLE trabajo_tecnico MODIFY COLUMN estado ENUM('PENDIENTE','EN_PROCESO','PAUSADO','FINALIZADO') NOT NULL DEFAULT 'PENDIENTE'");
    }

    public function down(): void
    {
        // Revertir cualquier registro PAUSADO a EN_PROCESO antes de quitar el valor
        DB::statement("UPDATE trabajo_tecnico SET estado = 'EN_PROCESO' WHERE estado = 'PAUSADO'");
        DB::statement("ALTER TABLE trabajo_tecnico MODIFY COLUMN estado ENUM('PENDIENTE','EN_PROCESO','FINALIZADO') NOT NULL DEFAULT 'PENDIENTE'");
    }
};
