<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El acceso lo controla el middleware CheckRole en la ruta.
    }

    public function rules(): array
    {
        $proveedorId = $this->route('proveedor')?->id;

        return [
            'nombre'   => ['required', 'string', 'max:150'],
            'nit'      => ['nullable', 'string', 'max:30', Rule::unique('proveedores', 'nit')->ignore($proveedorId)],
            'telefono' => ['nullable', 'string', 'max:25'],
        ];
    }

    public function attributes(): array
    {
        return ['nit' => 'Cédula / NIT'];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nit.unique'      => 'Ya existe un proveedor con esa Cédula / NIT.',
        ];
    }
}
