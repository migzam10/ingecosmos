<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige marcas_vehiculo donde se registró "MARCA MODELO" como una sola
     * entrada en lugar de separar marca y modelo correctamente.
     *
     * IDs de marcas reales ya existentes:
     *   CHEVROLET=2, KIA=3, NISSAN=10, VOLKSWAGEN=9
     *
     * IDs de modelos ya existentes que se reutilizan:
     *   Spark=12, BEAT=89, Frontier=42, K3=91, Golf=39
     */
    public function up(): void
    {
        // ── 1. Crear marcas reales que faltan ──────────────────────────────────
        $nuevasMarcas = ['FOTON', 'PEUGEOT', 'AUDI', 'JAC', 'BYD', 'CADILLAC',
                         'CITROEN', 'CHERY', 'MAHINDRA', 'RAM', 'SEAT', 'YAMAHA'];

        $ahora = now();
        foreach ($nuevasMarcas as $nombre) {
            DB::table('marcas_vehiculo')->insertOrIgnore([
                'nombre'     => $nombre,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        // Helper para obtener id de una marca por nombre
        $marca = fn(string $nombre) => DB::table('marcas_vehiculo')->where('nombre', $nombre)->value('id');

        // ── 2. Crear modelos nuevos bajo sus marcas reales ─────────────────────
        $modelosNuevos = [
            // [marca, modelo]
            ['FOTON',    'BJ'],
            ['FOTON',    'BJ10'],
            ['FOTON',    'BJ20'],
            ['FOTON',    'BJ50'],
            ['FOTON',    'BJ64'],
            ['FOTON',    'TUNLAND'],
            ['PEUGEOT',  '2008'],
            ['PEUGEOT',  '3008'],
            ['PEUGEOT',  '301'],
            ['AUDI',     'Q5'],
            ['JAC',      'T8'],
            ['JAC',      'HFCLL'],
            ['BYD',      'SEAGULL'],
            ['CADILLAC', 'ESCALADE'],
            ['CITROEN',  'C4'],
            ['CHERY',    'YOYA'],
            ['MAHINDRA', 'GETAWAY'],
            ['RAM',      'V700'],
            ['SEAT',     'ARONA'],
            ['YAMAHA',   'FZ-250'],
        ];

        foreach ($modelosNuevos as [$marcaNombre, $modeloNombre]) {
            $idMarca = $marca($marcaNombre);
            if (!$idMarca) continue;

            DB::table('modelos_vehiculo')->insertOrIgnore([
                'id_marca'   => $idMarca,
                'nombre'     => $modeloNombre,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        // Helper para obtener id de un modelo por marca+nombre
        $modelo = fn(int $idMarca, string $nombre) =>
            DB::table('modelos_vehiculo')
                ->where('id_marca', $idMarca)
                ->where('nombre', $nombre)
                ->value('id');

        // ── 3. Mapa: id_marca_falsa → [id_marca_real, id_modelo_real] ──────────
        $mapa = [
            // Con marca ya existente + modelo ya existente
            42 => [2,  12],   // CHEVOLET SPARK  → CHEVROLET(2)  + Spark(12)
            43 => [2,  89],   // CHEVROELT BEAT  → CHEVROLET(2)  + BEAT(89)
            22 => [10, 42],   // NISAN FRONTIER  → NISSAN(10)    + Frontier(42)
            20 => [3,  91],   // K3              → KIA(3)        + K3(91)
            19 => [9,  39],   // CARRO GOLF      → VOLKSWAGEN(9) + Golf(39)
        ];

        // Con marcas nuevas — resolvemos IDs en tiempo de ejecución
        $foton    = $marca('FOTON');
        $peugeot  = $marca('PEUGEOT');
        $audi     = $marca('AUDI');
        $jac      = $marca('JAC');
        $byd      = $marca('BYD');
        $cadillac = $marca('CADILLAC');
        $citroen  = $marca('CITROEN');
        $chery    = $marca('CHERY');
        $mahindra = $marca('MAHINDRA');
        $ram      = $marca('RAM');
        $seat     = $marca('SEAT');
        $yamaha   = $marca('YAMAHA');

        $mapa += [
            44 => [$foton,    $modelo($foton,    'BJ')],
            25 => [$foton,    $modelo($foton,    'BJ10')],
            36 => [$foton,    $modelo($foton,    'BJ20')],
            23 => [$foton,    $modelo($foton,    'BJ50')],
            31 => [$foton,    $modelo($foton,    'BJ64')],
            29 => [$foton,    $modelo($foton,    'TUNLAND')],
            21 => [$peugeot,  $modelo($peugeot,  '2008')],
            38 => [$peugeot,  $modelo($peugeot,  '3008')],
            18 => [$peugeot,  $modelo($peugeot,  '301')],
            17 => [$audi,     $modelo($audi,     'Q5')],
            32 => [$jac,      $modelo($jac,      'T8')],
            34 => [$jac,      $modelo($jac,      'HFCLL')],
            24 => [$byd,      $modelo($byd,      'SEAGULL')],
            39 => [$cadillac, $modelo($cadillac, 'ESCALADE')],
            27 => [$citroen,  $modelo($citroen,  'C4')],
            40 => [$chery,    $modelo($chery,    'YOYA')],
            30 => [$mahindra, $modelo($mahindra, 'GETAWAY')],
            26 => [$ram,      $modelo($ram,      'V700')],
            16 => [$seat,     $modelo($seat,     'ARONA')],
            28 => [$yamaha,   $modelo($yamaha,   'FZ-250')],
        ];

        // ── 4. Migrar vehículos ────────────────────────────────────────────────
        foreach ($mapa as $idMarcaFalsa => [$idMarcaReal, $idModeloReal]) {
            if (!$idMarcaReal) continue;

            DB::table('vehiculos')
                ->where('id_marca', $idMarcaFalsa)
                ->update([
                    'id_marca'   => $idMarcaReal,
                    'id_modelo'  => $idModeloReal,
                    'updated_at' => $ahora,
                ]);
        }

        // ── 5. Eliminar entradas falsas de marcas ──────────────────────────────
        DB::table('marcas_vehiculo')->whereIn('id', array_keys($mapa))->delete();
    }

    public function down(): void
    {
        // El rollback de esta migración no es seguro porque borra registros.
        // Se deja vacío intencionalmente.
    }
};
