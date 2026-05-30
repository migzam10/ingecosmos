<?php

namespace App\Http\Controllers;

use App\Models\FotoOt;
use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FotoOtController extends Controller
{
    // Subir una o varias fotos a una OT
    public function store(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'fotos'   => 'required|array|min:1|max:10',
            'fotos.*' => 'image|max:5120',
            'descripcion' => 'nullable|string|max:150',
        ]);

        $carpeta = 'fotos/' . $orden->numero_ot;

        foreach ($request->file('fotos') as $archivo) {
            $ruta = $archivo->store($carpeta, 'public');

            FotoOt::create([
                'id_ot'       => $orden->id,
                'subida_por'  => Auth::id(),
                'ruta'        => $ruta,
                'descripcion' => $request->descripcion,
            ]);
        }

        $cantidad = count($request->file('fotos'));
        $msg = $cantidad === 1 ? '1 foto subida correctamente.' : "{$cantidad} fotos subidas correctamente.";

        return back()->with('success', $msg);
    }

    // Eliminar una foto (ADMIN/COORDINADOR o quien la subió)
    public function destroy(FotoOt $foto)
    {
        $roles  = Auth::user()->roles ?? [];
        $esAdmin = in_array('ADMIN', $roles) || in_array('COORDINADOR', $roles);

        abort_unless($esAdmin || $foto->subida_por === Auth::id(), 403, 'No tienes permiso para eliminar esta foto.');

        Storage::disk('public')->delete($foto->ruta);
        $foto->delete();

        return back()->with('success', 'Foto eliminada.');
    }
}
