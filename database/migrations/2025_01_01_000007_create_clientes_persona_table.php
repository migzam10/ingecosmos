<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_persona', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('cedula', 20)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_persona');
    }
};
