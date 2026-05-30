<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientePersona extends Model
{
    protected $table = 'clientes_persona';
    protected $fillable = ['nombre', 'cedula', 'telefono', 'email'];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'id_cliente_persona');
    }
}
