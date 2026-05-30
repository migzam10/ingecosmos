<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCotizacionSuministro extends Model
{
    protected $table = 'items_cotizacion_suministro';
    protected $fillable = ['id_cotizacion', 'descripcion', 'costo', 'precio'];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }
}
