<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnexoOt extends Model
{
    protected $table = 'anexos_ot';

    protected $fillable = [
        'id_ot', 'titulo', 'ruta', 'nombre_original', 'subido_por',
    ];

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
