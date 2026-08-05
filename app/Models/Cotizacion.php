<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'numero_cot', 'id_ot', 'creada_por', 'estado', 'fecha_cotizacion',
        'subtotal_mo', 'subtotal_suministros', 'subtotal_rto', 'subtotal_insumos',
        'subtotal_terceros', 'subtotal_op',
        'descuento_porcentaje', 'descuento_valor',
        'iva_porcentaje', 'iva_valor', 'total', 'observaciones',
        // Previa (sin OT)
        'es_previa', 'placa_previa', 'km_previa', 'id_cliente_previa',
        'descripcion_previa', 'id_marca_previa', 'id_modelo_previa',
    ];

    protected function casts(): array
    {
        return [
            'es_previa'        => 'boolean',
            'fecha_cotizacion' => 'date',
            'iva_porcentaje'      => 'decimal:2',
            'iva_valor'           => 'decimal:2',
            'descuento_porcentaje'=> 'decimal:2',
            'descuento_valor'     => 'decimal:2',
        ];
    }

    /** Kilometraje a mostrar: el de la OT si existe, si no el de la previa. */
    public function kilometraje(): ?int
    {
        $km = $this->ot?->km_ingreso ?? $this->km_previa;
        return $km > 0 ? (int) $km : null;
    }

    public function ot()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_ot');
    }

    public function creadaPor()
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function itemsMo()
    {
        return $this->hasMany(ItemCotizacionMo::class, 'id_cotizacion');
    }

    public function itemsSuministro()
    {
        return $this->hasMany(ItemCotizacionSuministro::class, 'id_cotizacion');
    }

    public function itemsRepuesto()
    {
        return $this->hasMany(ItemCotizacionRepuesto::class, 'id_cotizacion');
    }

    public function itemsInsumo()
    {
        return $this->hasMany(ItemCotizacionInsumo::class, 'id_cotizacion');
    }

    public function clientePrevia()
    {
        return $this->belongsTo(ClientePersona::class, 'id_cliente_previa');
    }

    public function marcaPrevia()
    {
        return $this->belongsTo(MarcaVehiculo::class, 'id_marca_previa');
    }

    public function modeloPrevia()
    {
        return $this->belongsTo(ModeloVehiculo::class, 'id_modelo_previa');
    }
}
