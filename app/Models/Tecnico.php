<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    protected $table = 'tecnicos';

    protected $fillable = ['id_user', 'nombre', 'especialidades', 'deducciones_fijas', 'activo'];

    protected function casts(): array
    {
        return [
            'especialidades'    => 'array',
            'deducciones_fijas' => 'array',
            'activo'            => 'boolean',
        ];
    }

    /**
     * Obtiene el valor fijo predeterminado para una deducción.
     */
    public function getDeduccionFija(string $col): float
    {
        return (float) ($this->deducciones_fijas[$col] ?? 0);
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
