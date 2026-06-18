<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PausaTrabajo extends Model
{
    protected $table = 'pausas_trabajo';

    protected $fillable = [
        'id_trabajo', 'motivo', 'detenido_en', 'detenido_por', 'retomado_en', 'retomado_por',
    ];

    protected function casts(): array
    {
        return [
            'id_trabajo'   => 'integer',
            'detenido_en'  => 'date',
            'detenido_por' => 'integer',
            'retomado_en'  => 'date',
            'retomado_por' => 'integer',
        ];
    }

    public function trabajo()
    {
        return $this->belongsTo(TrabajoTecnico::class, 'id_trabajo');
    }

    public function detenidoPor()
    {
        return $this->belongsTo(User::class, 'detenido_por');
    }

    public function retomadoPor()
    {
        return $this->belongsTo(User::class, 'retomado_por');
    }

    // Pausa abierta = detenida y aún no retomada
    public function estaAbierta(): bool
    {
        return is_null($this->retomado_en);
    }
}
