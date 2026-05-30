<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoTecnico extends Model
{
    protected $table = 'pagos_tecnicos';

    protected $fillable = [
        'id_tecnico', 'id_user', 'id_ot', 'anio', 'mes', 'monto', 'tipo', 'concepto',
    ];

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico');
    }

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }
}
