<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ItemCotizacionInsumo extends Model
{
    protected $table = 'items_cotizacion_insumo';
    protected $fillable = ['id_cotizacion', 'id_insumo', 'descripcion', 'cantidad_solicitada', 'precio_venta', 'precio_total'];

    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'decimal:2',
            'precio_venta'        => 'decimal:2',
            'precio_total'        => 'decimal:2',
        ];
    }

    public function insumo()      { return $this->belongsTo(CatalogoInsumo::class, 'id_insumo'); }
    public function cotizacion()  { return $this->belongsTo(Cotizacion::class, 'id_cotizacion'); }
    public function salidasItems(){ return $this->hasMany(ItemSalidaAlmacen::class, 'id_item_cotizacion_insumo'); }

    public function getCantidadEntregadaAttribute(): float
    {
        return (float) $this->salidasItems()->sum('cantidad');
    }

    public function getCantidadPendienteAttribute(): float
    {
        return max(0, (float)$this->cantidad_solicitada - $this->cantidad_entregada);
    }
}
