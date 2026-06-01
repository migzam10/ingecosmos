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
        $invSimples = [
            'retrovisores', 'retrovisor_interno', 'radio', 'encendedor', 'pito',
            'tapizado', 'luz_techo', 'tapa_gasolina', 'llave_pernos', 'herramientas',
            'kit_carretera', 'gato', 'extintor', 'sensores', 'camara_reversa',
            'control_alarma', 'bateria', 'comando_ptas',
        ];
        $invCantidad = [
            'panoramicos', 'parlantes', 'rejillas_aa', 'plumillas',
            'cinturones', 'manijas', 'tapa_soles', 'tapetes',
        ];

        $rules = [
            'placa'                  => 'required|string|max:10',
            'id_marca'               => 'required|exists:marcas_vehiculo,id',
            'id_modelo'              => 'nullable|exists:modelos_vehiculo,id',
            'color'                  => 'nullable|string|max:50',
            'anio'                   => 'nullable|integer|min:1980|max:' . (date('Y') + 1),
            'nombre_cliente'         => 'required|string|max:150',
            'cedula_cliente'         => 'nullable|string|max:20',
            'telefono_cliente'       => 'nullable|string|max:20',
            'email_cliente'          => 'nullable|email|max:100',
            'direccion_cliente'      => 'nullable|string|max:200',
            'fecha_cumpleanos_cliente'=> 'nullable|date',
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
            'inv_observaciones'      => 'nullable|string|max:500',
        ];

        foreach ($invSimples as $c) {
            $rules["inv_{$c}"] = 'nullable|in:B,R,M';
        }
        foreach ($invCantidad as $c) {
            $rules["inv_{$c}_qty"] = 'nullable|integer|min:0|max:99';
            $rules["inv_{$c}"]     = 'nullable|in:B,R,M';
        }

        return $rules;
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
