<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioVehiculo extends Model
{
    protected $table = 'inventario_vehiculo';

    protected $fillable = [
        'id_ot',
        // sin cantidad
        'retrovisores', 'retrovisor_interno', 'radio', 'encendedor', 'pito',
        'tapizado', 'luz_techo', 'tapa_gasolina', 'llave_pernos', 'herramientas',
        'kit_carretera', 'gato', 'extintor', 'sensores', 'camara_reversa',
        'control_alarma', 'bateria', 'comando_ptas',
        // con cantidad
        'panoramicos_qty', 'panoramicos',
        'parlantes_qty', 'parlantes',
        'rejillas_aa_qty', 'rejillas_aa',
        'plumillas_qty', 'plumillas',
        'cinturones_qty', 'cinturones',
        'manijas_qty', 'manijas',
        'tapa_soles_qty', 'tapa_soles',
        'tapetes_qty', 'tapetes',
        'observaciones',
    ];

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }
}
