<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_tecnicos', function (Blueprint $table) {
            // Deducciones que se restan del valor de liquidación (todas opcionales).
            $table->decimal('ded_materiales', 12, 2)->default(0)->after('concepto');
            $table->decimal('ded_seguro_vida', 12, 2)->default(0)->after('ded_materiales');
            $table->decimal('ded_seguridad_social', 12, 2)->default(0)->after('ded_seguro_vida');
            $table->decimal('ded_recordar', 12, 2)->default(0)->after('ded_seguridad_social');
            $table->decimal('ded_ahorro_1', 12, 2)->default(0)->after('ded_recordar');
            $table->decimal('ded_ahorro_2', 12, 2)->default(0)->after('ded_ahorro_1');
            $table->decimal('ded_prestamo', 12, 2)->default(0)->after('ded_ahorro_2');
        });
    }

    public function down(): void
    {
        Schema::table('pagos_tecnicos', function (Blueprint $table) {
            $table->dropColumn([
                'ded_materiales', 'ded_seguro_vida', 'ded_seguridad_social',
                'ded_recordar', 'ded_ahorro_1', 'ded_ahorro_2', 'ded_prestamo',
            ]);
        });
    }
};
