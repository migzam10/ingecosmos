<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('numero_cot')->unique();
            $table->foreignId('id_ot')->constrained('ordenes_trabajo')->onDelete('cascade');
            $table->foreignId('creada_por')->constrained('users')->onDelete('restrict');

            $table->string('estado', 20)->default('BORRADOR'); // BORRADOR, ENVIADA, AUTORIZADA, RECHAZADA
            $table->decimal('subtotal_mo', 12, 2)->default(0);
            $table->decimal('subtotal_suministros', 12, 2)->default(0);
            $table->decimal('subtotal_rto', 12, 2)->default(0);
            $table->decimal('subtotal_terceros', 12, 2)->default(0);
            $table->decimal('subtotal_op', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
