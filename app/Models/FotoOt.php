<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoOt extends Model
{
    protected $table = 'fotos_ot';
    protected $fillable = ['id_ot', 'subida_por', 'ruta', 'descripcion'];

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }
}
