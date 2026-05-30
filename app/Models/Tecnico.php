<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    protected $table = 'tecnicos';

    protected $fillable = ['id_user', 'nombre', 'especialidades', 'activo'];

    protected function casts(): array
    {
        return [
            'especialidades' => 'array',
            'activo'         => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function trabajos()
    {
        return $this->hasMany(TrabajoTecnico::class, 'id_tecnico');
    }

    public function pagos()
    {
        return $this->hasMany(PagoTecnico::class, 'id_tecnico');
    }
}
