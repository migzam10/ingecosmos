<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComentarioTrabajo extends Model
{
    protected $table = 'comentarios_trabajo';
    protected $fillable = ['id_trabajo', 'id_tecnico', 'texto'];

    public function trabajo()
    {
        return $this->belongsTo(TrabajoTecnico::class, 'id_trabajo');
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico');
    }
}
