<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarcaVehiculo extends Model
{
    protected $table = 'marcas_vehiculo';
    protected $fillable = ['nombre'];

    public function modelos()
    {
        return $this->hasMany(ModeloVehiculo::class, 'id_marca');
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'id_marca');
    }

    public function catalogoMo()
    {
        return $this->hasMany(CatalogoMo::class, 'id_marca');
    }
}
