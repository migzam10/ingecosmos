<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trabajo_tecnico', function (Blueprint $table) {
            $table->integer('mes_cobro')->nullable()->after('fin_en');
            $table->integer('anio_cobro')->nullable()->after('mes_cobro');
        });

        // Populate existing data where fin_en is not null
        \Illuminate\Support\Facades\DB::statement('UPDATE trabajo_tecnico SET mes_cobro = MONTH(fin_en), anio_cobro = YEAR(fin_en) WHERE fin_en IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajo_tecnico', function (Blueprint $table) {
            $table->dropColumn(['mes_cobro', 'anio_cobro']);
        });
    }
};
