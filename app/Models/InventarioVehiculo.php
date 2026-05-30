<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioVehiculo extends Model
{
    protected $table = 'inventario_vehiculo';

    protected $fillable = [
        'id_ot',
        'parabrisas', 'vidrio_delantero_izq', 'vidrio_delantero_der',
        'vidrio_trasero_izq', 'vidrio_trasero_der', 'vidrio_trasero',
        'espejo_izq', 'espejo_der',
        'llanta_del_izq', 'llanta_del_der', 'llanta_tra_izq', 'llanta_tra_der',
        'llanta_repuesto', 'antena', 'radio', 'encendedor', 'gato', 'triangulo',
        'observaciones',
    ];

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }
}
