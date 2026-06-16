<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCotizacionRepuesto extends Model
{
    protected $table = 'items_cotizacion_repuesto';

    protected $fillable = [
        'id_cotizacion', 'id_catalogo_repuesto',
        'descripcion', 'unidades', 'precio_unitario', 'precio_total',
    ];

    protected function casts(): array
    {
        return [
            'unidades'       => 'decimal:2',
            'precio_unitario'=> 'decimal:2',
            'precio_total'   => 'decimal:2',
        ];
    }

    public function catalogo()
    {
        return $this->belongsTo(CatalogoRepuesto::class, 'id_catalogo_repuesto');
    }
}
