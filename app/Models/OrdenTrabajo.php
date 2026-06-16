<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    protected $table = 'ordenes_trabajo';

    protected $fillable = [
        'numero_ot', 'area', 'id_vehiculo', 'id_cliente_persona', 'id_empresa_cliente',
        'km_ingreso', 'referencia_forc', 'llaves_entregadas', 'documentos_entregados',
        'ingreso_grua', 'nivel_combustible', 'estado_proceso', 'estado_semaforo',
        'tg', 'dr', 'ha', 'num_piezas',
        'fecha_ingreso', 'fecha_cotizacion', 'fecha_autorizacion',
        'fecha_llegada_ultimo_rto', 'fecha_inicio_proceso', 'fecha_terminacion',
        'fecha_entrega_cliente', 'salida_estimada',
        'valor_mo', 'valor_rto', 'valor_insumos_pint', 'valor_terceros', 'valor_op', 'total',
        'pasado_a_facturar', 'numero_factura', 'fecha_factura',
        'costo_mo', 'costo_rto', 'costo_insumos', 'costo_total',
        'tecnico_lat', 'tecnico_prep', 'tecnico_pint', 'tecnico_mec', 'tecnico_elec',
        'tiene_scanner', 'observaciones', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso'           => 'date',
            'fecha_cotizacion'        => 'date',
            'fecha_autorizacion'      => 'date',
            'fecha_llegada_ultimo_rto'=> 'date',
            'fecha_inicio_proceso'    => 'date',
            'fecha_terminacion'       => 'date',
            'fecha_entrega_cliente'   => 'date',
            'salida_estimada'         => 'date',
            'llaves_entregadas'       => 'boolean',
            'documentos_entregados'   => 'boolean',
            'ingreso_grua'            => 'boolean',
            'pasado_a_facturar'       => 'boolean',
            'fecha_factura'           => 'date',
            'tiene_scanner'           => 'boolean',
        ];
    }

    // Relaciones
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }

    public function clientePersona()
    {
        return $this->belongsTo(ClientePersona::class, 'id_cliente_persona');
    }

    public function empresaCliente()
    {
        return $this->belongsTo(EmpresaCliente::class, 'id_empresa_cliente');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function tecnicoLat()
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_lat');
    }

    public function tecnicoPrep()
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_prep');
    }

    public function tecnicoPint()
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_pint');
    }

    public function tecnicoMec()
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_mec');
    }

    public function tecnicoElec()
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_elec');
    }

    public function inventario()
    {
        return $this->hasOne(InventarioVehiculo::class, 'id_ot');
    }

    public function fotos()
    {
        return $this->hasMany(FotoOt::class, 'id_ot');
    }

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'id_ot');
    }

    public function historial()
    {
        return $this->hasMany(HistorialOt::class, 'id_ot')->orderBy('created_at', 'desc');
    }

    public function trabajosTecnico()
    {
        return $this->hasMany(TrabajoTecnico::class, 'id_ot');
    }

    public function entregasParciales()
    {
        return $this->hasMany(EntregaParcial::class, 'id_ot')->orderByDesc('fecha_entrega');
    }
}
