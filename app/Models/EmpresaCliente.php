<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaCliente extends Model
{
    protected $table = 'empresas_cliente';

    protected $fillable = [
        'nombre', 'tipo', 'tarifa_hora',
        'meta_dias_leve', 'meta_dias_medio', 'meta_dias_fuerte', 'activa',
    ];

    protected function casts(): array
    {
        return [
            'tarifa_hora' => 'decimal:2',
            'activa'      => 'boolean',
        ];
    }

    public function ordenesTraabajo()
    {
        return $this->hasMany(OrdenTrabajo::class, 'id_empresa_cliente');
    }
}
