<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOrdenCompra extends Model
{
    protected $table = 'items_orden_compra';

    protected $fillable = [
        'id_orden_compra', 'cantidad', 'unidad', 'descripcion',
        'valor_unitario', 'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'       => 'decimal:2',
            'valor_unitario' => 'decimal:2',
            'valor_total'    => 'decimal:2',
        ];
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'id_orden_compra');
    }
}
