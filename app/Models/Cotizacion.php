<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'numero_cot', 'id_ot', 'creada_por', 'estado',
        'subtotal_mo', 'subtotal_suministros', 'subtotal_rto',
        'subtotal_terceros', 'subtotal_op', 'total', 'observaciones',
    ];

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }

    public function creadaPor()
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function itemsMo()
    {
        return $this->hasMany(ItemCotizacionMo::class, 'id_cotizacion');
    }

    public function itemsSuministro()
    {
        return $this->hasMany(ItemCotizacionSuministro::class, 'id_cotizacion');
    }
}
