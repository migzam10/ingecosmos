<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->decimal('subtotal_insumos', 15, 2)->default(0)->after('subtotal_rto');
        });

        Schema::create('items_cotizacion_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotizacion')->constrained('cotizaciones')->onDelete('cascade');
            $table->foreignId('id_insumo')->nullable()->constrained('catalogo_insumos')->onDelete('set null');
            $table->string('descripcion', 200);
            $table->decimal('cantidad_solicitada', 10, 2)->default(1);
            $table->decimal('precio_venta', 15, 2)->default(0);
            $table->decimal('precio_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('items_cotizacion_insumo');
        Schema::table('cotizaciones', fn($t) => $t->dropColumn('subtotal_insumos'));
    }
};
