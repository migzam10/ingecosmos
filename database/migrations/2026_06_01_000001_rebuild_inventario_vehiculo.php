<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: eliminar todas las columnas del inventario anterior
        Schema::table('inventario_vehiculo', function (Blueprint $table) {
            $table->dropColumn([
                'parabrisas', 'vidrio_delantero_izq', 'vidrio_delantero_der',
                'vidrio_trasero_izq', 'vidrio_trasero_der', 'vidrio_trasero',
                'espejo_izq', 'espejo_der',
                'llanta_del_izq', 'llanta_del_der', 'llanta_tra_izq', 'llanta_tra_der', 'llanta_repuesto',
                'antena', 'radio', 'encendedor', 'gato', 'triangulo',
            ]);
        });

        // Paso 2: agregar los ítems del inventario físico real
        Schema::table('inventario_vehiculo', function (Blueprint $table) {
            // --- Ítems sin cantidad (solo B/R/M) ---
            $table->enum('retrovisores',     ['B','R','M'])->nullable()->after('id_ot');
            $table->enum('retrovisor_interno',['B','R','M'])->nullable()->after('retrovisores');
            $table->enum('radio',            ['B','R','M'])->nullable()->after('retrovisor_interno');
            $table->enum('encendedor',       ['B','R','M'])->nullable()->after('radio');
            $table->enum('pito',             ['B','R','M'])->nullable()->after('encendedor');
            $table->enum('tapizado',         ['B','R','M'])->nullable()->after('pito');
            $table->enum('luz_techo',        ['B','R','M'])->nullable()->after('tapizado');
            $table->enum('tapa_gasolina',    ['B','R','M'])->nullable()->after('luz_techo');
            $table->enum('llave_pernos',     ['B','R','M'])->nullable()->after('tapa_gasolina');
            $table->enum('herramientas',     ['B','R','M'])->nullable()->after('llave_pernos');
            $table->enum('kit_carretera',    ['B','R','M'])->nullable()->after('herramientas');
            $table->enum('gato',             ['B','R','M'])->nullable()->after('kit_carretera');
            $table->enum('extintor',         ['B','R','M'])->nullable()->after('gato');
            $table->enum('sensores',         ['B','R','M'])->nullable()->after('extintor');
            $table->enum('camara_reversa',   ['B','R','M'])->nullable()->after('sensores');
            $table->enum('control_alarma',   ['B','R','M'])->nullable()->after('camara_reversa');
            $table->enum('bateria',          ['B','R','M'])->nullable()->after('control_alarma');
            $table->enum('comando_ptas',     ['B','R','M'])->nullable()->after('bateria');

            // --- Ítems con cantidad + B/R/M ---
            $table->unsignedTinyInteger('panoramicos_qty')->nullable()->after('comando_ptas');
            $table->enum('panoramicos',  ['B','R','M'])->nullable()->after('panoramicos_qty');

            $table->unsignedTinyInteger('parlantes_qty')->nullable()->after('panoramicos');
            $table->enum('parlantes',    ['B','R','M'])->nullable()->after('parlantes_qty');

            $table->unsignedTinyInteger('rejillas_aa_qty')->nullable()->after('parlantes');
            $table->enum('rejillas_aa',  ['B','R','M'])->nullable()->after('rejillas_aa_qty');

            $table->unsignedTinyInteger('plumillas_qty')->nullable()->after('rejillas_aa');
            $table->enum('plumillas',    ['B','R','M'])->nullable()->after('plumillas_qty');

            $table->unsignedTinyInteger('cinturones_qty')->nullable()->after('plumillas');
            $table->enum('cinturones',   ['B','R','M'])->nullable()->after('cinturones_qty');

            $table->unsignedTinyInteger('manijas_qty')->nullable()->after('cinturones');
            $table->enum('manijas',      ['B','R','M'])->nullable()->after('manijas_qty');

            $table->unsignedTinyInteger('tapa_soles_qty')->nullable()->after('manijas');
            $table->enum('tapa_soles',   ['B','R','M'])->nullable()->after('tapa_soles_qty');

            $table->unsignedTinyInteger('tapetes_qty')->nullable()->after('tapa_soles');
            $table->enum('tapetes',      ['B','R','M'])->nullable()->after('tapetes_qty');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_vehiculo', function (Blueprint $table) {
            $table->dropColumn([
                'retrovisores', 'retrovisor_interno', 'radio', 'encendedor', 'pito',
                'tapizado', 'luz_techo', 'tapa_gasolina', 'llave_pernos', 'herramientas',
                'kit_carretera', 'gato', 'extintor', 'sensores', 'camara_reversa',
                'control_alarma', 'bateria', 'comando_ptas',
                'panoramicos_qty', 'panoramicos',
                'parlantes_qty', 'parlantes',
                'rejillas_aa_qty', 'rejillas_aa',
                'plumillas_qty', 'plumillas',
                'cinturones_qty', 'cinturones',
                'manijas_qty', 'manijas',
                'tapa_soles_qty', 'tapa_soles',
                'tapetes_qty', 'tapetes',
            ]);
        });

        Schema::table('inventario_vehiculo', function (Blueprint $table) {
            $table->enum('parabrisas', ['B','R','M'])->nullable()->after('id_ot');
            $table->enum('vidrio_delantero_izq', ['B','R','M'])->nullable()->after('parabrisas');
            $table->enum('vidrio_delantero_der', ['B','R','M'])->nullable();
            $table->enum('vidrio_trasero_izq',   ['B','R','M'])->nullable();
            $table->enum('vidrio_trasero_der',   ['B','R','M'])->nullable();
            $table->enum('vidrio_trasero',       ['B','R','M'])->nullable();
            $table->enum('espejo_izq',           ['B','R','M'])->nullable();
            $table->enum('espejo_der',           ['B','R','M'])->nullable();
            $table->enum('llanta_del_izq',       ['B','R','M'])->nullable();
            $table->enum('llanta_del_der',       ['B','R','M'])->nullable();
            $table->enum('llanta_tra_izq',       ['B','R','M'])->nullable();
            $table->enum('llanta_tra_der',       ['B','R','M'])->nullable();
            $table->enum('llanta_repuesto',      ['B','R','M'])->nullable();
            $table->enum('antena',               ['B','R','M'])->nullable();
            $table->enum('radio',                ['B','R','M'])->nullable();
            $table->enum('encendedor',           ['B','R','M'])->nullable();
            $table->enum('gato',                 ['B','R','M'])->nullable();
            $table->enum('triangulo',            ['B','R','M'])->nullable();
        });
    }
};
