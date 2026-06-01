<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_tecnicos', function (Blueprint $table) {
            $table->date('fecha_pago')->nullable()->after('concepto');
        });

        // Los pagos ya existentes heredan su created_at como fecha_pago
        \DB::statement('UPDATE pagos_tecnicos SET fecha_pago = DATE(created_at)');
    }

    public function down(): void
    {
        Schema::table('pagos_tecnicos', function (Blueprint $table) {
            $table->dropColumn('fecha_pago');
        });
    }
};
