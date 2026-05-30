<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('numero_ot')->unique();
            $table->enum('area', ['LYP', 'MECANICA'])->default('LYP');

            // Relaciones principales
            $table->foreignId('id_vehiculo')->constrained('vehiculos')->onDelete('restrict');
            $table->foreignId('id_cliente_persona')->nullable()->constrained('clientes_persona')->onDelete('set null');
            $table->foreignId('id_empresa_cliente')->constrained('empresas_cliente')->onDelete('restrict');

            // Datos de ingreso
            $table->unsignedInteger('km_ingreso')->default(0);
            $table->string('referencia_forc', 50)->nullable();
            $table->boolean('llaves_entregadas')->default(false);
            $table->boolean('documentos_entregados')->default(false);
            $table->boolean('ingreso_grua')->default(false);
            $table->unsignedTinyInteger('nivel_combustible')->default(0); // 0-10

            // Estado y semáforo
            $table->string('estado_proceso', 30)->default('PTE_COTIZACION');
            $table->string('estado_semaforo', 20)->default('SIN_FECHA');

            // Cálculos
            $table->enum('tg', ['Leve', 'Medio', 'Fuerte'])->nullable();
            $table->unsignedTinyInteger('dr')->nullable();
            $table->decimal('ha', 8, 2)->nullable();
            $table->unsignedSmallInteger('num_piezas')->default(0);

            // Fechas clave
            $table->date('fecha_ingreso');
            $table->date('fecha_cotizacion')->nullable();
            $table->date('fecha_autorizacion')->nullable();
            $table->date('fecha_llegada_ultimo_rto')->nullable();
            $table->date('fecha_inicio_proceso')->nullable();
            $table->date('fecha_terminacion')->nullable();
            $table->date('fecha_entrega_cliente')->nullable();
            $table->date('salida_estimada')->nullable();

            // Valores facturación
            $table->decimal('valor_mo', 12, 2)->default(0);
            $table->decimal('valor_rto', 12, 2)->default(0);
            $table->decimal('valor_insumos_pint', 12, 2)->default(0);
            $table->decimal('valor_terceros', 12, 2)->default(0);
            $table->decimal('valor_op', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->boolean('pasado_a_facturar')->default(false);

            // Costos internos
            $table->decimal('costo_mo', 12, 2)->default(0);
            $table->decimal('costo_rto', 12, 2)->default(0);
            $table->decimal('costo_insumos', 12, 2)->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);

            // Técnicos asignados (FK nullable)
            $table->foreignId('tecnico_lat')->nullable()->constrained('tecnicos')->onDelete('set null');
            $table->foreignId('tecnico_prep')->nullable()->constrained('tecnicos')->onDelete('set null');
            $table->foreignId('tecnico_pint')->nullable()->constrained('tecnicos')->onDelete('set null');
            $table->foreignId('tecnico_mec')->nullable()->constrained('tecnicos')->onDelete('set null');
            $table->foreignId('tecnico_elec')->nullable()->constrained('tecnicos')->onDelete('set null');
            $table->boolean('tiene_scanner')->default(false);

            $table->text('observaciones')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->constrained('users')->onDelete('restrict');
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
