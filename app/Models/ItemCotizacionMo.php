<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCotizacionMo extends Model
{
    protected $table = 'items_cotizacion_mo';
    protected $fillable = ['id_cotizacion', 'id_catalogo_mo', 'descripcion', 'precio'];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    public function catalogoMo()
    {
        return $this->belongsTo(CatalogoMo::class, 'id_catalogo_mo');
    }
}
