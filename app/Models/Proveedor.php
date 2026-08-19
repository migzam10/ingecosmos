<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    protected $fillable = ['nombre', 'nit', 'telefono'];

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'id_proveedor');
    }
}
