<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function update(Request $request, OrdenTrabajo $orden)
    {
        $campos = [
            'parabrisas','vidrio_delantero_izq','vidrio_delantero_der',
            'vidrio_trasero_izq','vidrio_trasero_der','vidrio_trasero',
            'espejo_izq','espejo_der',
            'llanta_del_izq','llanta_del_der','llanta_tra_izq','llanta_tra_der','llanta_repuesto',
            'antena','radio','encendedor','gato','triangulo',
        ];

        $rules = ['observaciones' => 'nullable|string|max:500'];
        foreach ($campos as $c) {
            $rules["inv_{$c}"] = 'nullable|in:B,R,M';
        }
        $request->validate($rules);

        $data = ['observaciones' => $request->inv_observaciones];
        foreach ($campos as $c) {
            $data[$c] = $request->input("inv_{$c}");
        }

        $orden->inventario?->update($data);

        return back()->with('success', 'Inventario actualizado correctamente.');
    }
}
