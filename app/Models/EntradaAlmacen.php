<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EntradaAlmacen extends Model
{
    protected $table = 'entradas_almacen';
    protected $fillable = ['fecha', 'observaciones', 'creado_por'];
    protected function casts(): array { return ['fecha' => 'date']; }

    public function items()    { return $this->hasMany(ItemEntradaAlmacen::class, 'id_entrada'); }
    public function creadoPor(){ return $this->belongsTo(User::class, 'creado_por'); }
}
