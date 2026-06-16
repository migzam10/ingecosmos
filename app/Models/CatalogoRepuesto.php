<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoRepuesto extends Model
{
    protected $table = 'catalogo_repuestos';

    protected $fillable = ['descripcion', 'precio_referencia', 'activo'];

    protected function casts(): array
    {
        return [
            'activo'            => 'boolean',
            'precio_referencia' => 'decimal:2',
        ];
    }
}
