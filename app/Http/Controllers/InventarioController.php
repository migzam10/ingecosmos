<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    // Campos solo B/R/M (sin cantidad)
    const CAMPOS_SIMPLES = [
        'retrovisores', 'retrovisor_interno', 'radio', 'encendedor', 'pito',
        'tapizado', 'luz_techo', 'tapa_gasolina', 'llave_pernos', 'herramientas',
        'kit_carretera', 'gato', 'extintor', 'sensores', 'camara_reversa',
        'control_alarma', 'bateria', 'comando_ptas',
    ];

    // Campos con cantidad + B/R/M
    const CAMPOS_CON_CANTIDAD = [
        'panoramicos', 'parlantes', 'rejillas_aa', 'plumillas',
        'cinturones', 'manijas', 'tapa_soles', 'tapetes',
    ];

    public function update(Request $request, OrdenTrabajo $orden)
    {
        $rules = ['inv_observaciones' => 'nullable|string|max:500'];

        foreach (self::CAMPOS_SIMPLES as $c) {
            $rules["inv_{$c}"] = 'nullable|in:B,R,M';
        }
        foreach (self::CAMPOS_CON_CANTIDAD as $c) {
            $rules["inv_{$c}_qty"] = 'nullable|integer|min:0|max:99';
            $rules["inv_{$c}"]     = 'nullable|in:B,R,M';
        }

        $request->validate($rules);

        $data = ['observaciones' => $request->inv_observaciones];

        foreach (self::CAMPOS_SIMPLES as $c) {
            $data[$c] = $request->input("inv_{$c}");
        }
        foreach (self::CAMPOS_CON_CANTIDAD as $c) {
            $data["{$c}_qty"] = $request->input("inv_{$c}_qty");
            $data[$c]         = $request->input("inv_{$c}");
        }

        $orden->inventario?->update($data);

        return back()->with('success', 'Inventario actualizado correctamente.');
    }
}
