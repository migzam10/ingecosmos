<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SalidaAlmacen extends Model
{
    protected $table = 'salidas_almacen';
    protected $fillable = ['tipo', 'id_cotizacion', 'id_ot', 'entregado_a', 'fecha', 'observaciones', 'creado_por'];
    protected function casts(): array { return ['fecha' => 'date']; }

    public function items()     { return $this->hasMany(ItemSalidaAlmacen::class, 'id_salida'); }
    public function cotizacion(){ return $this->belongsTo(Cotizacion::class, 'id_cotizacion'); }
    public function ot()        { return $this->belongsTo(OrdenTrabajo::class, 'id_ot'); }
    public function creadoPor() { return $this->belongsTo(User::class, 'creado_por'); }
}
