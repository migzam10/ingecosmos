<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoTecnico extends Model
{
    protected $table = 'pagos_tecnicos';

    // Deducciones que se restan del valor de liquidación: columna => etiqueta (en orden).
    public const DEDUCCIONES = [
        'ded_materiales'        => 'Materiales',
        'ded_seguro_vida'       => 'Seguro de vida',
        'ded_seguridad_social'  => 'Seguridad social',
        'ded_recordar'          => 'Recordar',
        'ded_ahorro_1'          => 'Ahorro 1 (10%)',
        'ded_ahorro_2'          => 'Ahorro 2',
        'ded_prestamo'          => 'Prestamo',
    ];

    protected $fillable = [
        'id_tecnico', 'id_user', 'id_ot', 'anio', 'mes', 'monto', 'tipo', 'concepto', 'fecha_pago',
        'ded_materiales', 'ded_seguro_vida', 'ded_seguridad_social',
        'ded_recordar', 'ded_ahorro_1', 'ded_ahorro_2', 'ded_prestamo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago'           => 'date',
            'ded_materiales'       => 'decimal:2',
            'ded_seguro_vida'      => 'decimal:2',
            'ded_seguridad_social' => 'decimal:2',
            'ded_recordar'         => 'decimal:2',
            'ded_ahorro_1'         => 'decimal:2',
            'ded_ahorro_2'         => 'decimal:2',
            'ded_prestamo'         => 'decimal:2',
        ];
    }

    /** Suma de todas las deducciones de este pago. */
    public function totalDeducciones(): float
    {
        $t = 0;
        foreach (array_keys(self::DEDUCCIONES) as $col) {
            $t += (float) $this->{$col};
        }
        return $t;
    }

    public function registradoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'id_user');
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico');
    }

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }
}
