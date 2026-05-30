<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaParcial extends Model
{
    protected $table = 'entregas_parciales';

    protected $fillable = [
        'id_ot', 'fecha_entrega', 'descripcion', 'fecha_retorno', 'motivo_retorno',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrega'  => 'date',
            'fecha_retorno'  => 'date',
        ];
    }

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }
}
