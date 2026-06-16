<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ItemSalidaAlmacen extends Model
{
    protected $table = 'items_salida_almacen';
    protected $fillable = ['id_salida', 'id_insumo', 'id_item_cotizacion_insumo', 'descripcion', 'cantidad', 'precio_venta'];
    protected function casts(): array
    {
        return ['cantidad' => 'decimal:2', 'precio_venta' => 'decimal:2'];
    }
    public function insumo()        { return $this->belongsTo(CatalogoInsumo::class, 'id_insumo'); }
    public function salida()        { return $this->belongsTo(SalidaAlmacen::class, 'id_salida'); }
    public function itemCotizacion(){ return $this->belongsTo(ItemCotizacionInsumo::class, 'id_item_cotizacion_insumo'); }
}
