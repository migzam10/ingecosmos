<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = [
        'placa', 'id_marca', 'id_modelo', 'color', 'anio', 'id_cliente_persona',
    ];

    public function marca()
    {
        return $this->belongsTo(MarcaVehiculo::class, 'id_marca');
    }

    public function modelo()
    {
        return $this->belongsTo(ModeloVehiculo::class, 'id_modelo');
    }

    public function clientePersona()
    {
        return $this->belongsTo(ClientePersona::class, 'id_cliente_persona');
    }

    public function ordenesTraabajo()
    {
        return $this->hasMany(OrdenTrabajo::class, 'id_vehiculo');
    }
}
