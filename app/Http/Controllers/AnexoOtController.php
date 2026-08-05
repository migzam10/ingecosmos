<?php

namespace App\Http\Controllers;

use App\Models\AnexoOt;
use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AnexoOtController extends Controller
{
    /** Solo administrador y coordinador gestionan anexos. */
    private function autorizar(): void
    {
        abort_unless(
            Auth::user()->hasAnyRole(['ADMIN', 'COORDINADOR']),
            403,
            'No tienes permiso para gestionar anexos.'
        );
    }

    // Subir un anexo PDF a la OT
    public function store(Request $request, OrdenTrabajo $orden)
    {
        $this->autorizar();

        $request->validate([
            'titulo'  => 'required|string|max:150',
            'archivo' => 'required|file|mimes:pdf|max:10240', // 10 MB
        ]);

        $carpeta = 'anexos/' . $orden->numero_ot;
        $ruta    = $request->file('archivo')->store($carpeta, 'public');

        AnexoOt::create([
            'id_ot'           => $orden->id,
            'titulo'          => trim($request->titulo),
            'ruta'            => $ruta,
            'nombre_original' => $request->file('archivo')->getClientOriginalName(),
            'subido_por'      => Auth::id(),
        ]);

        return back()->with('success', 'Anexo subido correctamente.');
    }

    // Eliminar un anexo
    public function destroy(AnexoOt $anexo)
    {
        $this->autorizar();

        Storage::disk('public')->delete($anexo->ruta);
        $anexo->delete();

        return back()->with('success', 'Anexo eliminado.');
    }
}
