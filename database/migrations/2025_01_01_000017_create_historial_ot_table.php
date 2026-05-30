<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_ot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ot')->constrained('ordenes_trabajo')->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users')->onDelete('restrict');
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->text('comentario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_ot');
    }
};
