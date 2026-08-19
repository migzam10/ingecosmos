<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero', 'fecha', 'forma_pago',
        'id_proveedor', 'numero_ot', 'placa', 'id_marca', 'id_modelo',
        'subtotal', 'descuento_porcentaje', 'descuento_valor',
        'iva_porcentaje', 'iva_valor', 'total',
        'observaciones', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha'                => 'date',
            'subtotal'             => 'decimal:2',
            'descuento_porcentaje' => 'decimal:2',
            'descuento_valor'      => 'decimal:2',
            'iva_porcentaje'       => 'decimal:2',
            'iva_valor'            => 'decimal:2',
            'total'                => 'decimal:2',
        ];
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function marca()
    {
        return $this->belongsTo(MarcaVehiculo::class, 'id_marca');
    }

    public function modelo()
    {
        return $this->belongsTo(ModeloVehiculo::class, 'id_modelo');
    }

    public function items()
    {
        return $this->hasMany(ItemOrdenCompra::class, 'id_orden_compra');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
