<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarOrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El acceso lo controla el middleware CheckRole en la ruta.
    }

    public function rules(): array
    {
        // En edición ignora el propio registro para el unique del consecutivo.
        $ordenId = $this->route('ordenCompra')?->id;

        return [
            'numero'     => ['required', 'integer', 'min:1', Rule::unique('ordenes_compra', 'numero')->ignore($ordenId)],
            'fecha'      => ['required', 'date', 'before_or_equal:today'],
            'forma_pago' => ['required', Rule::in(['CREDITO', 'CONTADO'])],

            'proveedor_nombre'   => ['required', 'string', 'max:150'],
            'proveedor_nit'      => ['nullable', 'string', 'max:30'],
            'proveedor_telefono' => ['nullable', 'string', 'max:25'],

            'numero_ot' => ['nullable', 'string', 'max:50'],
            'placa'     => ['nullable', 'string', 'max:10'],
            'id_marca'  => ['nullable', 'exists:marcas_vehiculo,id'],
            'id_modelo' => ['nullable', 'exists:modelos_vehiculo,id'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.cantidad'       => ['required', 'numeric', 'min:0.01'],
            'items.*.unidad'         => ['nullable', 'string', 'max:20'],
            'items.*.descripcion'    => ['required', 'string', 'max:255'],
            'items.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.valor_total'    => ['required', 'numeric', 'min:0'],

            'descuento_valor' => ['nullable', 'numeric', 'min:0'],
            'iva_valor'       => ['nullable', 'numeric', 'min:0'],

            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'numero'           => 'número de orden',
            'proveedor_nombre' => 'nombre del proveedor',
            'items'            => 'productos',
        ];
    }

    public function messages(): array
    {
        return [
            'numero.unique' => 'Ya existe una orden de compra con ese número. Usa uno distinto.',
            'items.required' => 'Debes agregar al menos un producto.',
            'items.min'      => 'Debes agregar al menos un producto.',
        ];
    }
}
