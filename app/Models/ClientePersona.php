<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientePersona extends Model
{
    protected $table = 'clientes_persona';
    protected $fillable = ['nombre', 'cedula', 'telefono', 'email', 'direccion', 'fecha_cumpleanos'];

    protected function casts(): array
    {
        return ['fecha_cumpleanos' => 'date'];
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'id_cliente_persona');
    }
}
