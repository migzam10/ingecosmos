<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CatalogoInsumo extends Model
{
    protected $table = 'catalogo_insumos';
    protected $fillable = ['nombre', 'unidad_medida', 'precio_venta', 'stock_minimo', 'stock_actual', 'activo'];

    protected function casts(): array
    {
        return [
            'activo'       => 'boolean',
            'precio_venta' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'stock_actual' => 'decimal:2',
        ];
    }

    public function getStockBajoAttribute(): bool
    {
        return (float)$this->stock_actual <= (float)$this->stock_minimo;
    }

    public function itemsEntrada()   { return $this->hasMany(ItemEntradaAlmacen::class, 'id_insumo'); }
    public function itemsSalida()    { return $this->hasMany(ItemSalidaAlmacen::class,  'id_insumo'); }
    public function itemsCotizacion(){ return $this->hasMany(ItemCotizacionInsumo::class,'id_insumo'); }
}
