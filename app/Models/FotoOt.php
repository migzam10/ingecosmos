<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoOt extends Model
{
    protected $table = 'fotos_ot';
    protected $fillable = ['id_ot', 'id_trabajo', 'subida_por', 'ruta', 'descripcion'];

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }

    public function subidaPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'subida_por');
    }

    public function trabajo()
    {
        return $this->belongsTo(TrabajoTecnico::class, 'id_trabajo');
    }
}
