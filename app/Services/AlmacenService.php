<?php

namespace App\Services;

use App\Models\CatalogoInsumo;
use App\Models\Cotizacion;
use App\Models\EntradaAlmacen;
use App\Models\ItemCotizacionInsumo;
use App\Models\ItemEntradaAlmacen;
use App\Models\ItemSalidaAlmacen;
use App\Models\OrdenTrabajo;
use App\Models\SalidaAlmacen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlmacenService
{
    public function registrarEntrada(array $data): EntradaAlmacen
    {
        return DB::transaction(function () use ($data) {
            $entrada = EntradaAlmacen::create([
                'fecha'         => $data['fecha'],
                'observaciones' => $data['observaciones'] ?? null,
                'creado_por'    => Auth::id(),
            ]);

            foreach (($data['items'] ?? []) as $item) {
                if (empty($item['id_insumo']) || empty($item['cantidad'])) continue;
                $cantidad = (float) $item['cantidad'];
                if ($cantidad <= 0) continue;

                ItemEntradaAlmacen::create([
                    'id_entrada'   => $entrada->id,
                    'id_insumo'    => $item['id_insumo'],
                    'cantidad'     => $cantidad,
                    'precio_compra'=> isset($item['precio_compra']) && $item['precio_compra'] !== '' ? (float)$item['precio_compra'] : null,
                ]);

                CatalogoInsumo::where('id', $item['id_insumo'])
                    ->increment('stock_actual', $cantidad);
            }

            return $entrada->load('items.insumo');
        });
    }

    public function registrarSalida(array $data): SalidaAlmacen
    {
        return DB::transaction(function () use ($data) {
            $salida = SalidaAlmacen::create([
                'tipo'          => $data['tipo'],
                'id_cotizacion' => $data['id_cotizacion'] ?? null,
                'id_ot'         => $data['id_ot'] ?? null,
                'entregado_a'   => $data['entregado_a'],
                'fecha'         => $data['fecha'],
                'observaciones' => $data['observaciones'] ?? null,
                'creado_por'    => Auth::id(),
            ]);

            foreach (($data['items'] ?? []) as $item) {
                if (empty($item['id_insumo']) || empty($item['cantidad'])) continue;
                $cantidad = (float) $item['cantidad'];
                if ($cantidad <= 0) continue;

                $insumo = CatalogoInsumo::lockForUpdate()->findOrFail($item['id_insumo']);

                if ((float)$insumo->stock_actual < $cantidad) {
                    throw new \RuntimeException(
                        "Stock insuficiente para «{$insumo->nombre}». Disponible: {$insumo->stock_actual} {$insumo->unidad_medida}."
                    );
                }

                if (!empty($item['id_item_cotizacion_insumo'])) {
                    $itemCot = ItemCotizacionInsumo::find($item['id_item_cotizacion_insumo']);
                    if ($itemCot && $cantidad > $itemCot->cantidad_pendiente) {
                        throw new \RuntimeException(
                            "No puede entregar {$cantidad} de «{$insumo->nombre}»: solo quedan {$itemCot->cantidad_pendiente} {$insumo->unidad_medida} pendientes en la cotización."
                        );
                    }
                }

                ItemSalidaAlmacen::create([
                    'id_salida'                => $salida->id,
                    'id_insumo'                => $item['id_insumo'],
                    'id_item_cotizacion_insumo'=> $item['id_item_cotizacion_insumo'] ?? null,
                    'descripcion'              => $item['descripcion'],
                    'cantidad'                 => $cantidad,
                    'precio_venta'             => (float)($item['precio_venta'] ?? 0),
                ]);

                $insumo->decrement('stock_actual', $cantidad);
            }

            return $salida->load('items.insumo');
        });
    }

    public function eliminarEntrada(EntradaAlmacen $entrada): void
    {
        DB::transaction(function () use ($entrada) {
            $entrada->load('items');
            foreach ($entrada->items as $item) {
                CatalogoInsumo::where('id', $item->id_insumo)
                    ->decrement('stock_actual', (float) $item->cantidad);
            }
            $entrada->delete();
        });
    }

    public function actualizarEntrada(EntradaAlmacen $entrada, array $data): EntradaAlmacen
    {
        return DB::transaction(function () use ($entrada, $data) {
            // Revertir stock de los ítems anteriores
            $entrada->load('items');
            foreach ($entrada->items as $item) {
                CatalogoInsumo::where('id', $item->id_insumo)
                    ->decrement('stock_actual', (float) $item->cantidad);
            }
            $entrada->items()->delete();

            // Actualizar cabecera
            $entrada->update([
                'fecha'         => $data['fecha'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Aplicar nuevos ítems
            foreach (($data['items'] ?? []) as $item) {
                if (empty($item['id_insumo']) || empty($item['cantidad'])) continue;
                $cantidad = (float) $item['cantidad'];
                if ($cantidad <= 0) continue;

                ItemEntradaAlmacen::create([
                    'id_entrada'    => $entrada->id,
                    'id_insumo'     => $item['id_insumo'],
                    'cantidad'      => $cantidad,
                    'precio_compra' => isset($item['precio_compra']) && $item['precio_compra'] !== ''
                        ? (float) $item['precio_compra'] : null,
                ]);

                CatalogoInsumo::where('id', $item['id_insumo'])
                    ->increment('stock_actual', $cantidad);
            }

            return $entrada->load('items.insumo');
        });
    }

    public function eliminarSalida(SalidaAlmacen $salida): void
    {
        DB::transaction(function () use ($salida) {
            $salida->load('items');
            foreach ($salida->items as $item) {
                CatalogoInsumo::where('id', $item->id_insumo)
                    ->increment('stock_actual', (float) $item->cantidad);
            }
            $salida->delete();
            // Al eliminar los ItemSalidaAlmacen (cascade), cantidad_entregada de los
            // ItemCotizacionInsumo referenciados disminuye automáticamente → vuelven a pendiente
        });
    }

    public function actualizarSalida(SalidaAlmacen $salida, array $data): SalidaAlmacen
    {
        return DB::transaction(function () use ($salida, $data) {
            // Revertir stock de los ítems anteriores (devolver al inventario)
            $salida->load('items');
            foreach ($salida->items as $item) {
                CatalogoInsumo::where('id', $item->id_insumo)
                    ->increment('stock_actual', (float) $item->cantidad);
            }
            $salida->items()->delete();
            // Tras borrar los ItemSalidaAlmacen, cantidad_pendiente de los ítems de
            // cotización aumenta → el check posterior usa el estado correcto

            // Actualizar cabecera
            $salida->update([
                'entregado_a'   => $data['entregado_a'],
                'fecha'         => $data['fecha'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Aplicar nuevos ítems con validaciones
            foreach (($data['items'] ?? []) as $item) {
                if (empty($item['id_insumo']) || empty($item['cantidad'])) continue;
                $cantidad = (float) $item['cantidad'];
                if ($cantidad <= 0) continue;

                $insumo = CatalogoInsumo::lockForUpdate()->findOrFail($item['id_insumo']);

                if ((float) $insumo->stock_actual < $cantidad) {
                    throw new \RuntimeException(
                        "Stock insuficiente para «{$insumo->nombre}». Disponible: {$insumo->stock_actual} {$insumo->unidad_medida}."
                    );
                }

                if (!empty($item['id_item_cotizacion_insumo'])) {
                    $itemCot = ItemCotizacionInsumo::find($item['id_item_cotizacion_insumo']);
                    if ($itemCot && $cantidad > $itemCot->cantidad_pendiente) {
                        throw new \RuntimeException(
                            "No puede entregar {$cantidad} de «{$insumo->nombre}»: solo quedan {$itemCot->cantidad_pendiente} {$insumo->unidad_medida} pendientes."
                        );
                    }
                }

                ItemSalidaAlmacen::create([
                    'id_salida'                 => $salida->id,
                    'id_insumo'                 => $item['id_insumo'],
                    'id_item_cotizacion_insumo' => $item['id_item_cotizacion_insumo'] ?? null,
                    'descripcion'               => $item['descripcion'],
                    'cantidad'                  => $cantidad,
                    'precio_venta'              => (float) ($item['precio_venta'] ?? 0),
                ]);

                $insumo->decrement('stock_actual', $cantidad);
            }

            return $salida->load('items.insumo');
        });
    }

    /** Insumos de una cotización con su cantidad pendiente de entrega */
    public function getPendientesCotizacion(Cotizacion $cot): Collection
    {
        return $cot->itemsInsumo()
            ->with(['insumo', 'salidasItems'])
            ->get()
            ->map(function ($item) {
                $item->append(['cantidad_entregada', 'cantidad_pendiente']);
                return $item;
            })
            ->filter(fn($item) => $item->cantidad_pendiente > 0);
    }

    /**
     * Insumos pendientes de una OT, agregando TODAS sus cotizaciones AUTORIZADAS.
     * Así una salida puede cubrir varias cotizaciones (o entregarse por partes en
     * distintos días). Las cotizaciones no autorizadas (BORRADOR) NO participan.
     */
    public function getPendientesOT(OrdenTrabajo $ot): Collection
    {
        return ItemCotizacionInsumo::with(['insumo', 'salidasItems', 'cotizacion'])
            ->whereHas('cotizacion', fn($q) => $q->where('id_ot', $ot->id)->where('estado', 'AUTORIZADA'))
            ->get()
            ->map(fn($item) => $item->append(['cantidad_entregada', 'cantidad_pendiente']))
            ->filter(fn($item) => $item->cantidad_pendiente > 0)
            ->values();
    }

    /** Todos los insumos pendientes agrupados por OT */
    public function getPendientesPorOT(): Collection
    {
        return ItemCotizacionInsumo::with(['insumo', 'cotizacion.ot.vehiculo.marca', 'salidasItems'])
            ->whereHas('cotizacion', fn($q) => $q->where('estado', 'AUTORIZADA'))
            ->get()
            ->map(fn($item) => $item->append(['cantidad_entregada', 'cantidad_pendiente']))
            ->filter(fn($item) => $item->cantidad_pendiente > 0)
            ->groupBy(fn($item) => $item->cotizacion?->id_ot ?? 0);
    }

    /** Insumos con stock bajo (stock_actual <= stock_minimo) */
    public function getAlertasStockBajo(): Collection
    {
        return CatalogoInsumo::where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->orderBy('nombre')
            ->get();
    }

    /** Insumos cuya demanda pendiente supera el stock disponible */
    public function getAlertasDemanda(): Collection
    {
        return ItemCotizacionInsumo::with(['insumo', 'cotizacion', 'salidasItems'])
            ->whereHas('cotizacion', fn($q) => $q->where('estado', 'AUTORIZADA'))
            ->get()
            ->map(fn($item) => $item->append(['cantidad_entregada', 'cantidad_pendiente']))
            ->filter(fn($item) => $item->cantidad_pendiente > 0)
            ->groupBy('id_insumo')
            ->map(function ($items) {
                $insumo          = $items->first()->insumo;
                $totalPendiente  = $items->sum('cantidad_pendiente');
                $stockActual     = (float)($insumo?->stock_actual ?? 0);
                return [
                    'insumo'          => $insumo,
                    'total_pendiente' => $totalPendiente,
                    'stock_actual'    => $stockActual,
                    'deficit'         => max(0, $totalPendiente - $stockActual),
                    'items'           => $items,
                ];
            })
            ->filter(fn($r) => $r['deficit'] > 0)
            ->sortByDesc('deficit');
    }
}
