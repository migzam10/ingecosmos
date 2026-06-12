<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrabajoTecnico extends Model
{
    protected $table = 'trabajo_tecnico';

    protected $fillable = [
        'id_ot', 'id_tecnico', 'especialidad', 'fecha_asignacion', 'estado',
        'inicio_en', 'fin_en', 'valor_liquidar', 'liquidado', 'comentarios',
    ];

    protected function casts(): array
    {
        return [
            'id_ot'            => 'integer',
            'id_tecnico'       => 'integer',
            'fecha_asignacion' => 'date',
            'inicio_en'        => 'datetime',
            'fin_en'           => 'datetime',
            'liquidado'        => 'boolean',
        ];
    }

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico');
    }

    public function historialComentarios()
    {
        return $this->hasMany(ComentarioTrabajo::class, 'id_trabajo')->orderBy('created_at');
    }

    public function fotos()
    {
        return $this->hasMany(FotoOt::class, 'id_trabajo')->orderBy('created_at');
    }
}
