<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoRepuesto extends Model
{
    protected $table = 'catalogo_repuestos';

    protected $fillable = [
        'nivel', 'id_marca', 'id_modelo', 'descripcion', 'precio_referencia', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function marca()
    {
        return $this->belongsTo(MarcaVehiculo::class, 'id_marca');
    }

    public function modelo()
    {
        return $this->belongsTo(ModeloVehiculo::class, 'id_modelo');
    }
}
