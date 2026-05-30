<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tecnico')->constrained('tecnicos')->onDelete('restrict');
            $table->foreignId('id_user')->constrained('users')->onDelete('restrict');
            $table->foreignId('id_ot')->nullable()->constrained('ordenes_trabajo')->onDelete('set null');

            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes'); // 1-12
            $table->decimal('monto', 12, 2);
            $table->enum('tipo', ['ABONO', 'PAGO_FINAL', 'ANTICIPO'])->default('ABONO');
            $table->text('concepto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_tecnicos');
    }
};
