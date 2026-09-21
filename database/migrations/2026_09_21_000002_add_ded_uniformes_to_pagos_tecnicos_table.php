<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_tecnicos', function (Blueprint $table) {
            $table->decimal('ded_uniformes', 12, 2)->default(0)->after('ded_prestamo');
        });
    }

    public function down(): void
    {
        Schema::table('pagos_tecnicos', function (Blueprint $table) {
            $table->dropColumn('ded_uniformes');
        });
    }
};
