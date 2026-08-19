<?php

namespace App\Services;

use App\Models\ItemOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Secuencia;
use Illuminate\Support\Facades\DB;

class OrdenCompraService
{
    /** Sugiere el siguiente consecutivo (editable en el formulario). Empieza en 1. */
    public function sugerirNumero(): int
    {
        $sec = Secuencia::where('tipo', 'ORDEN_COMPRA')->value('ultimo_numero') ?? 0;
        $max = OrdenCompra::max('numero') ?? 0;

        return max($sec, $max) + 1;
    }

    public function crear(array $data, int $userId): OrdenCompra
    {
        return DB::transaction(function () use ($data, $userId) {
            $proveedorId = $this->resolverProveedor($data);
            [$subtotal, $descVal, $ivaVal, $total] = $this->calcularTotales($data);

            $orden = OrdenCompra::create([
                'numero'               => $data['numero'],
                'fecha'                => $data['fecha'],
                'forma_pago'           => $data['forma_pago'],
                'id_proveedor'         => $proveedorId,
                'numero_ot'            => $data['numero_ot'] ?? null,
                'placa'                => isset($data['placa']) ? strtoupper(trim($data['placa'])) : null,
                'id_marca'             => $data['id_marca'] ?? null,
                'id_modelo'            => $data['id_modelo'] ?? null,
                'subtotal'             => $subtotal,
                'descuento_porcentaje' => $this->pct($subtotal, $descVal),
                'descuento_valor'      => $descVal,
                'iva_porcentaje'       => $this->pct($subtotal - $descVal, $ivaVal),
                'iva_valor'            => $ivaVal,
                'total'                => $total,
                'observaciones'        => $data['observaciones'] ?? null,
                'creado_por'           => $userId,
            ]);

            $this->guardarItems($orden, $data['items'] ?? []);
            $this->subirSecuencia((int) $data['numero']);

            return $orden;
        });
    }

    public function actualizar(OrdenCompra $orden, array $data, int $userId): OrdenCompra
    {
        return DB::transaction(function () use ($orden, $data, $userId) {
            $proveedorId = $this->resolverProveedor($data);
            [$subtotal, $descVal, $ivaVal, $total] = $this->calcularTotales($data);

            $orden->update([
                'numero'               => $data['numero'],
                'fecha'                => $data['fecha'],
                'forma_pago'           => $data['forma_pago'],
                'id_proveedor'         => $proveedorId,
                'numero_ot'            => $data['numero_ot'] ?? null,
                'placa'                => isset($data['placa']) ? strtoupper(trim($data['placa'])) : null,
                'id_marca'             => $data['id_marca'] ?? null,
                'id_modelo'            => $data['id_modelo'] ?? null,
                'subtotal'             => $subtotal,
                'descuento_porcentaje' => $this->pct($subtotal, $descVal),
                'descuento_valor'      => $descVal,
                'iva_porcentaje'       => $this->pct($subtotal - $descVal, $ivaVal),
                'iva_valor'            => $ivaVal,
                'total'                => $total,
                'observaciones'        => $data['observaciones'] ?? null,
                'actualizado_por'      => $userId,
            ]);

            $orden->items()->delete();
            $this->guardarItems($orden, $data['items'] ?? []);
            $this->subirSecuencia((int) $data['numero']);

            return $orden;
        });
    }

    /** Busca el proveedor por NIT; si no existe lo crea, si existe actualiza nombre/teléfono. */
    private function resolverProveedor(array $data): ?int
    {
        $nombre = trim($data['proveedor_nombre'] ?? '');
        $nit    = trim($data['proveedor_nit'] ?? '');
        $tel    = trim($data['proveedor_telefono'] ?? '');

        if ($nombre === '' && $nit === '') {
            return null;
        }

        if ($nit !== '') {
            $prov = Proveedor::firstOrNew(['nit' => $nit]);
            $prov->nombre   = $nombre !== '' ? $nombre : ($prov->nombre ?: $nit);
            $prov->telefono = $tel !== '' ? $tel : $prov->telefono;
            $prov->save();

            return $prov->id;
        }

        // Sin NIT: se crea un proveedor suelto para dejar registro.
        return Proveedor::create([
            'nombre'   => $nombre,
            'telefono' => $tel ?: null,
        ])->id;
    }

    /** Devuelve [$subtotal, $descuentoValor, $ivaValor, $total]. Descuento va antes del IVA. */
    private function calcularTotales(array $data): array
    {
        $subtotal = collect($data['items'] ?? [])
            ->sum(fn ($i) => (float) ($i['valor_total'] ?? 0));

        $descVal = isset($data['descuento_valor']) && $data['descuento_valor'] !== ''
            ? min(max(0, (float) $data['descuento_valor']), $subtotal)
            : 0;

        $baseIva = $subtotal - $descVal;

        $ivaVal = isset($data['iva_valor']) && $data['iva_valor'] !== ''
            ? max(0, (float) $data['iva_valor'])
            : 0;

        $total = $baseIva + $ivaVal;

        return [(float) $subtotal, (float) $descVal, (float) $ivaVal, (float) $total];
    }

    private function pct(float $base, float $valor): float
    {
        return $base > 0 ? round($valor / $base * 100, 2) : 0;
    }

    private function guardarItems(OrdenCompra $orden, array $items): void
    {
        foreach ($items as $it) {
            $desc = trim($it['descripcion'] ?? '');
            if ($desc === '') {
                continue;
            }
            ItemOrdenCompra::create([
                'id_orden_compra' => $orden->id,
                'cantidad'        => (float) ($it['cantidad'] ?? 1),
                'unidad'          => $it['unidad'] ?? null,
                'descripcion'     => $desc,
                'valor_unitario'  => (float) ($it['valor_unitario'] ?? 0),
                'valor_total'     => (float) ($it['valor_total'] ?? 0),
            ]);
        }
    }

    private function subirSecuencia(int $numero): void
    {
        $sec = Secuencia::firstOrCreate(['tipo' => 'ORDEN_COMPRA'], ['ultimo_numero' => 0]);
        if ($numero > $sec->ultimo_numero) {
            $sec->update(['ultimo_numero' => $numero]);
        }
    }
}
