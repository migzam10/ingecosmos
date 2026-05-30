<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class MigracionController extends Controller
{
    public function index()
    {
        $totalOTs     = OrdenTrabajo::count();
        $archivoLocal = file_exists(base_path('TORRE_CONTROL_2025.xlsx'));

        return view('admin.migracion.index', compact('totalOTs', 'archivoLocal'));
    }

    public function ejecutar(Request $request)
    {
        // El Excel histórico puede tardar varios minutos en procesar
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $archivo = base_path('TORRE_CONTROL_2025.xlsx');

        // Si subieron un archivo nuevo, usarlo
        if ($request->hasFile('archivo_excel')) {
            $request->validate([
                'archivo_excel' => 'required|file|mimes:xlsx,xls',
            ]);
            $path    = $request->file('archivo_excel')->storeAs('imports', 'torre_import.xlsx');
            $archivo = storage_path("app/{$path}");
        }

        if (!file_exists($archivo)) {
            return back()->with('error', 'No se encontró el archivo Excel. Sube uno o coloca TORRE_CONTROL_2025.xlsx en la raíz del proyecto.');
        }

        // Ejecutar el comando capturando la salida
        $exitCode = Artisan::call('importar:excel', ['archivo' => $archivo]);
        $salida   = Artisan::output();

        // Parsear resultados del output
        preg_match('/Importadas\s*\|\s*(\d+)/',  $salida, $mImport);
        preg_match('/Ya exist[íi]an\s*\|\s*(\d+)/', $salida, $mOmit);
        preg_match('/Errores\s*\|\s*(\d+)/',     $salida, $mErr);

        $resultado = [
            'importadas' => (int) ($mImport[1] ?? 0),
            'omitidas'   => (int) ($mOmit[1]  ?? 0),
            'errores'    => (int) ($mErr[1]    ?? 0),
            'salida'     => $salida,
            'exitCode'   => $exitCode,
        ];

        return view('admin.migracion.resultado', compact('resultado'));
    }
}
