<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ConfiguracionEmpresa extends Model
{
    protected $table = 'configuracion_empresa';

    protected $fillable = [
        'razon_social', 'nit', 'direccion', 'ciudad',
        'telefono', 'email', 'logo', 'pie_pagina_pdf',
    ];

    /** Retorna la única fila de configuración, o instancia vacía si no existe. */
    public static function getActual(): self
    {
        return static::first() ?? new static();
    }

    /** URL pública del logo (para vistas Blade). */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }

    /** Ruta absoluta del logo (para dompdf). */
    public function getLogoPathAttribute(): ?string
    {
        return $this->logo ? storage_path('app/public/' . $this->logo) : null;
    }
}
