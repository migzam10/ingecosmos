<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialOt extends Model
{
    protected $table = 'historial_ot';

    protected $fillable = [
        'id_ot', 'id_user', 'estado_anterior', 'estado_nuevo', 'comentario', 'fecha_evento',
    ];

    protected function casts(): array
    {
        return ['fecha_evento' => 'date'];
    }

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
