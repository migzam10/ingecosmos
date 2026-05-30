<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloVehiculo extends Model
{
    protected $table = 'modelos_vehiculo';
    protected $fillable = ['id_marca', 'nombre'];

    public function marca()
    {
        return $this->belongsTo(MarcaVehiculo::class, 'id_marca');
    }
}
