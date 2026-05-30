<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            // El Excel acepta valores como 1.5, 2.5 (piezas parcialmente dañadas)
            $table->decimal('num_piezas', 5, 1)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            $table->unsignedSmallInteger('num_piezas')->default(0)->change();
        });
    }
};
