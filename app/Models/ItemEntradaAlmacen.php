<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ItemEntradaAlmacen extends Model
{
    protected $table = 'items_entrada_almacen';
    protected $fillable = ['id_entrada', 'id_insumo', 'cantidad', 'precio_compra'];
    protected function casts(): array
    {
        return ['cantidad' => 'decimal:2', 'precio_compra' => 'decimal:2'];
    }
    public function insumo() { return $this->belongsTo(CatalogoInsumo::class, 'id_insumo'); }
    public function entrada(){ return $this->belongsTo(EntradaAlmacen::class, 'id_entrada'); }
}
