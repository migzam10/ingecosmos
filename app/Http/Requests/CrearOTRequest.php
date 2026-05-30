<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearOTRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['ADMIN', 'COORDINADOR', 'RECEPCION']);
    }

    public function rules(): array
    {
        return [
            'placa'                  => 'required|string|max:10',
            'id_marca'               => 'required|exists:marcas_vehiculo,id',
            'id_modelo'              => 'nullable|exists:modelos_vehiculo,id',
            'color'                  => 'nullable|string|max:50',
            'anio'                   => 'nullable|integer|min:1980|max:' . (date('Y') + 1),
            'nombre_cliente'         => 'required|string|max:150',
            'cedula_cliente'         => 'nullable|string|max:20',
            'telefono_cliente'       => 'nullable|string|max:20',
            'email_cliente'          => 'nullable|email|max:100',
            'id_empresa_cliente'     => 'required|exists:empresas_cliente,id',
            'area'                   => 'required|in:LYP,MECANICA',
            'km_ingreso'             => 'required|integer|min:0',
            'referencia_forc'        => 'nullable|string|max:50',
            'llaves_entregadas'      => 'boolean',
            'documentos_entregados'  => 'boolean',
            'ingreso_grua'           => 'boolean',
            'nivel_combustible'      => 'required|integer|min:0|max:10',
            'fecha_ingreso'          => 'required|date',
            'observaciones'          => 'nullable|string|max:1000',
            // Inventario B/R/G
            'inv_parabrisas'              => 'nullable|in:B,R,M',
            'inv_vidrio_delantero_izq'    => 'nullable|in:B,R,M',
            'inv_vidrio_delantero_der'    => 'nullable|in:B,R,M',
            'inv_vidrio_trasero_izq'      => 'nullable|in:B,R,M',
            'inv_vidrio_trasero_der'      => 'nullable|in:B,R,M',
            'inv_vidrio_trasero'          => 'nullable|in:B,R,M',
            'inv_espejo_izq'              => 'nullable|in:B,R,M',
            'inv_espejo_der'              => 'nullable|in:B,R,M',
            'inv_llanta_del_izq'          => 'nullable|in:B,R,M',
            'inv_llanta_del_der'          => 'nullable|in:B,R,M',
            'inv_llanta_tra_izq'          => 'nullable|in:B,R,M',
            'inv_llanta_tra_der'          => 'nullable|in:B,R,M',
            'inv_llanta_repuesto'         => 'nullable|in:B,R,M',
            'inv_antena'                  => 'nullable|in:B,R,M',
            'inv_radio'                   => 'nullable|in:B,R,M',
            'inv_encendedor'              => 'nullable|in:B,R,M',
            'inv_gato'                    => 'nullable|in:B,R,M',
            'inv_triangulo'               => 'nullable|in:B,R,M',
            'inv_observaciones'           => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'placa.required'              => 'La placa es obligatoria.',
            'id_marca.required'           => 'Seleccione la marca del vehículo.',
            'nombre_cliente.required'     => 'El nombre del cliente es obligatorio.',
            'id_empresa_cliente.required' => 'Seleccione la empresa / tipo de cliente.',
            'area.required'               => 'Seleccione el área del taller.',
            'km_ingreso.required'         => 'El kilometraje de ingreso es obligatorio.',
            'fecha_ingreso.required'      => 'La fecha de ingreso es obligatoria.',
        ];
    }
}
