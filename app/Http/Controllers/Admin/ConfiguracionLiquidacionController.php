<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionLiquidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionLiquidacionController extends Controller
{
    public function index()
    {
        $config = ConfiguracionLiquidacion::getActual();
        return view('admin.configuracion_liquidacion.index', compact('config'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'razon_social'   => 'required|string|max:200',
            'nit'            => 'required|string|max:30',
            'direccion'      => 'nullable|string|max:300',
            'ciudad'         => 'nullable|string|max:100',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'logo'           => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'pie_pagina_pdf' => 'nullable|string|max:300',
        ]);

        $config = ConfiguracionLiquidacion::first();

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($config && $config->logo) {
                Storage::delete($config->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos_liquidacion', 'public');
        } else {
            unset($data['logo']);
        }

        if ($config) {
            $config->update($data);
        } else {
            ConfiguracionLiquidacion::create($data);
        }

        return redirect()->route('admin.configuracion_liquidacion.index')
            ->with('success', 'Configuración de liquidaciones guardada correctamente.');
    }

    public function eliminarLogo()
    {
        $config = ConfiguracionLiquidacion::first();
        if ($config && $config->logo) {
            Storage::delete($config->logo);
            $config->update(['logo' => null]);
        }
        return redirect()->route('admin.configuracion_liquidacion.index')
            ->with('success', 'Logo de liquidaciones eliminado.');
    }
}
