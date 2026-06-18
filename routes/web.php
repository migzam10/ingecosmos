<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrdenTrabajoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\CorreccionTrabajoController;
use App\Http\Controllers\Admin\EmpresaClienteController;
use App\Http\Controllers\Admin\MigracionController;
use App\Http\Controllers\Admin\TecnicoAdminController;
use App\Http\Controllers\Admin\FestivoController;
use App\Http\Controllers\Admin\MarcaModeloController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AsignarTecnicoController;
use App\Http\Controllers\EntregaParcialController;
use App\Http\Controllers\EstadoOTController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CatalogoMoController;
use App\Http\Controllers\CatalogoRepuestosController;
use App\Http\Controllers\CatalogoInsumosController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\EntradaAlmacenController;
use App\Http\Controllers\SalidaAlmacenController;
use App\Http\Controllers\LiquidacionController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\MisTareasController;
use App\Http\Controllers\FotoOtController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\TorreController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/app-deshabilitada', fn() => view('app-deshabilitada'))->name('app.deshabilitada');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Órdenes de Trabajo
// Acceso al módulo: COTIZADOR puede ver. Crear/editar/borrar lo controla
// CrearOTRequest::authorize() y los abort_if del controller.
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,RECEPCION,COTIZADOR'])->group(function () {
    Route::resource('ordenes', OrdenTrabajoController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->parameters(['ordenes' => 'orden']);

    // Fotos de OT
    Route::post('/ordenes/{orden}/fotos', [FotoOtController::class, 'store'])->name('fotos.store');
    Route::delete('/fotos/{foto}', [FotoOtController::class, 'destroy'])->name('fotos.destroy');
    // Inventario B/R/G
    Route::put('/ordenes/{orden}/inventario', [InventarioController::class, 'update'])->name('inventario.update');
});

// AJAX endpoints
Route::middleware(['auth'])->group(function () {
    Route::get('/api/placa',               [OrdenTrabajoController::class,      'buscarPlaca'])->name('api.placa');
    Route::get('/api/modelos',             [OrdenTrabajoController::class,      'modelosPorMarca'])->name('api.modelos');
    Route::get('/api/catalogo-mo',         [CatalogoMoController::class,        'porVehiculo'])->name('api.catalogo-mo');
    Route::get('/api/catalogo-repuestos',  [CatalogoRepuestosController::class, 'porVehiculo'])->name('api.catalogo-repuestos');
    Route::get('/api/catalogo-insumos',    [CatalogoInsumosController::class,   'buscar'])->name('api.catalogo-insumos');
    Route::get('/api/salidas/pendientes/{cotizacion}', [SalidaAlmacenController::class, 'pendientesCotizacion'])->name('api.salidas.pendientes');
});

// Transiciones de estado OT (Coordinador/Admin)
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->prefix('ordenes/{orden}/estado')->group(function () {
    Route::post('/autorizar',          [EstadoOTController::class, 'autorizar'])->name('ot.autorizar');
    Route::post('/orden-repuestos',    [EstadoOTController::class, 'ordenRepuestos'])->name('ot.orden-repuestos');
    Route::post('/repuestos-llegaron', [EstadoOTController::class, 'repuestosLlegaron'])->name('ot.repuestos-llegaron');
    Route::post('/iniciar-proceso',    [EstadoOTController::class, 'iniciarProceso'])->name('ot.iniciar-proceso');
    Route::post('/programar-entrega',  [EstadoOTController::class, 'programarEntrega'])->name('ot.programar-entrega');
    Route::post('/entregar',           [EstadoOTController::class, 'entregar'])->name('ot.entregar');
    Route::post('/especial',           [EstadoOTController::class, 'estadoEspecial'])->name('ot.especial');
});

// Facturación de OT — solo cuando está ENTREGADA
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::post('/ordenes/{orden}/factura', [OrdenTrabajoController::class, 'guardarFactura'])->name('ot.factura');
});

// Cambiar número de OT — solo si no tiene cotizaciones
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,RECEPCION'])->group(function () {
    Route::patch('/ordenes/{orden}/numero', [OrdenTrabajoController::class, 'cambiarNumero'])->name('ot.cambiar-numero');
});

// Entregas parciales
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::post('/ordenes/{orden}/entrega-parcial',          [EntregaParcialController::class, 'store'])->name('entregas-parciales.store');
    Route::post('/entregas-parciales/{entregaParcial}/retorno', [EntregaParcialController::class, 'retorno'])->name('entregas-parciales.retorno');
});

// Asignación de técnicos (Coordinador/Admin)
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::post('/ordenes/{orden}/tecnicos',            [AsignarTecnicoController::class, 'store'])->name('ordenes.tecnicos.store');
    Route::delete('/ordenes/{orden}/tecnicos/{trabajo}',[AsignarTecnicoController::class, 'destroy'])->name('ordenes.tecnicos.destroy');
    Route::post('/trabajos/{trabajo}/valor',            [LiquidacionController::class, 'guardarValorOT'])->name('trabajos.valor');
});

// Liquidación de técnicos
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::get('/liquidacion',                           [LiquidacionController::class, 'index'])->name('liquidacion.index');
    Route::get('/liquidacion/{tecnico}',                 [LiquidacionController::class, 'show'])->name('liquidacion.show');
    Route::post('/liquidacion/{tecnico}/avance',         [LiquidacionController::class, 'registrarAvance'])->name('liquidacion.avance');
    Route::get('/liquidacion/{tecnico}/pdf',             [LiquidacionController::class, 'pdf'])->name('liquidacion.pdf');
    Route::delete('/pagos/{pago}',                       [LiquidacionController::class, 'eliminarPago'])->name('pagos.eliminar');
});

// Producción y KPIs
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::get('/produccion',                [ProduccionController::class, 'index'])->name('produccion.index');
    Route::get('/produccion/exportar',       [ProduccionController::class, 'exportar'])->name('produccion.exportar');
    Route::get('/produccion/excel-clientes', [ProduccionController::class, 'excelClientes'])->name('produccion.excel-clientes');
});

// Cotizaciones (COTIZADOR también puede gestionar cotizaciones)
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,COTIZADOR'])->group(function () {
    Route::get('/cotizaciones',                                [CotizacionController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/{cotizacion}',                   [CotizacionController::class, 'show'])->name('cotizaciones.show');
    Route::get('/cotizaciones/{cotizacion}/pdf',               [CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');
    Route::get('/cotizaciones/{cotizacion}/editar',            [CotizacionController::class, 'edit'])->name('cotizaciones.edit');
    Route::put('/cotizaciones/{cotizacion}',                   [CotizacionController::class, 'update'])->name('cotizaciones.update');
    Route::delete('/cotizaciones/{cotizacion}',                [CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');
    Route::get('/ordenes/{orden}/cotizar',                     [CotizacionController::class, 'create'])->name('cotizaciones.create');
    Route::post('/ordenes/{orden}/cotizar',                    [CotizacionController::class, 'store'])->name('cotizaciones.store');
    // Cotización previa (sin OT)
    Route::get('/cotizaciones/previa/crear',                   [CotizacionController::class, 'createPrevia'])->name('cotizaciones.previa.create');
    Route::post('/cotizaciones/previa',                        [CotizacionController::class, 'storePrevia'])->name('cotizaciones.previa.store');
    Route::post('/cotizaciones/{cotizacion}/vincular-ot',      [CotizacionController::class, 'vincularOT'])->name('cotizaciones.vincular-ot');
});

// Módulo Almacén (ADMIN + ALMACEN + COORDINADOR)
Route::middleware(['auth', 'role:ADMIN,ALMACEN,COORDINADOR'])->prefix('almacen')->name('almacen.')->group(function () {
    Route::get('/',                                [AlmacenController::class,       'index'])->name('index');
    Route::get('/catalogo',                        [CatalogoInsumosController::class,'index'])->name('catalogo.index');
    Route::post('/catalogo',                       [CatalogoInsumosController::class,'store'])->name('catalogo.store');
    Route::put('/catalogo/{catalogoInsumo}',       [CatalogoInsumosController::class,'update'])->name('catalogo.update');
    Route::delete('/catalogo/{catalogoInsumo}',    [CatalogoInsumosController::class,'destroy'])->name('catalogo.destroy');
    Route::post('/catalogo/{catalogoInsumo}/restaurar', [CatalogoInsumosController::class,'restaurar'])->name('catalogo.restaurar');

    Route::get('/entradas',                        [EntradaAlmacenController::class,'index'])->name('entradas.index');
    Route::get('/entradas/crear',                  [EntradaAlmacenController::class,'create'])->name('entradas.create');
    Route::post('/entradas',                       [EntradaAlmacenController::class,'store'])->name('entradas.store');
    Route::get('/entradas/{entrada}',              [EntradaAlmacenController::class,'show'])->name('entradas.show');
    // Edit/delete solo ADMIN y COORDINADOR
    Route::middleware('role:ADMIN,COORDINADOR')->group(function () {
        Route::get('/entradas/{entrada}/editar',   [EntradaAlmacenController::class,'edit'])->name('entradas.edit');
        Route::put('/entradas/{entrada}',          [EntradaAlmacenController::class,'update'])->name('entradas.update');
        Route::delete('/entradas/{entrada}',       [EntradaAlmacenController::class,'destroy'])->name('entradas.destroy');
    });

    Route::get('/salidas',                         [SalidaAlmacenController::class,'index'])->name('salidas.index');
    Route::get('/salidas/crear',                   [SalidaAlmacenController::class,'create'])->name('salidas.create');
    Route::post('/salidas',                        [SalidaAlmacenController::class,'store'])->name('salidas.store');
    Route::get('/salidas/{salida}',                [SalidaAlmacenController::class,'show'])->name('salidas.show');
    // Edit/delete solo ADMIN y COORDINADOR
    Route::middleware('role:ADMIN,COORDINADOR')->group(function () {
        Route::get('/salidas/{salida}/editar',     [SalidaAlmacenController::class,'edit'])->name('salidas.edit');
        Route::put('/salidas/{salida}',            [SalidaAlmacenController::class,'update'])->name('salidas.update');
        Route::delete('/salidas/{salida}',         [SalidaAlmacenController::class,'destroy'])->name('salidas.destroy');
    });

    Route::get('/ots',                             [AlmacenController::class,'ots'])->name('ots.index');
    Route::get('/ots/{orden}',                     [AlmacenController::class,'otShow'])->name('ots.show');
    Route::get('/pendientes',                      [AlmacenController::class,'pendientes'])->name('pendientes');
});

// Catálogo de Repuestos (solo ADMIN)
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    Route::get('/catalogo-repuestos',                               [CatalogoRepuestosController::class, 'index'])->name('catalogo-repuestos.index');
    Route::get('/catalogo-repuestos/crear',                         [CatalogoRepuestosController::class, 'create'])->name('catalogo-repuestos.create');
    Route::post('/catalogo-repuestos',                              [CatalogoRepuestosController::class, 'store'])->name('catalogo-repuestos.store');
    Route::get('/catalogo-repuestos/{catalogoRepuesto}/editar',     [CatalogoRepuestosController::class, 'edit'])->name('catalogo-repuestos.edit');
    Route::put('/catalogo-repuestos/{catalogoRepuesto}',            [CatalogoRepuestosController::class, 'update'])->name('catalogo-repuestos.update');
    Route::delete('/catalogo-repuestos/{catalogoRepuesto}',         [CatalogoRepuestosController::class, 'destroy'])->name('catalogo-repuestos.destroy');
    Route::post('/catalogo-repuestos/{catalogoRepuesto}/restaurar', [CatalogoRepuestosController::class, 'restaurar'])->name('catalogo-repuestos.restaurar');
});

// Torre de Control
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::get('/torre', [TorreController::class, 'index'])->name('torre.index');
});

// Correcciones del trabajo del técnico (reversa — solo ADMIN)
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    Route::post('/trabajos/{trabajo}/correccion/deshacer-finalizar', [CorreccionTrabajoController::class, 'deshacerFinalizar'])->name('correccion-trabajo.deshacer-finalizar');
    Route::post('/trabajos/{trabajo}/correccion/deshacer-inicio',     [CorreccionTrabajoController::class, 'deshacerInicio'])->name('correccion-trabajo.deshacer-inicio');
    Route::post('/trabajos/{trabajo}/correccion/fechas',              [CorreccionTrabajoController::class, 'editarFechas'])->name('correccion-trabajo.fechas');
});

Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::resource('catalogo', CatalogoMoController::class)
        ->parameters(['catalogo' => 'catalogo'])
        ->except(['show']);
    Route::post('/catalogo/{catalogo}/restaurar', [CatalogoMoController::class, 'restaurar'])->name('catalogo.restaurar');
});

Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    // Admin index
    Route::get('/admin', fn() => redirect()->route('admin.usuarios.index'))->name('admin.index');

    // Usuarios
    Route::resource('admin/usuarios', UsuarioController::class)
        ->names('admin.usuarios')
        ->parameters(['usuarios' => 'usuario']);

    // Técnicos
    Route::resource('admin/tecnicos', TecnicoAdminController::class)
        ->names('admin.tecnicos')
        ->parameters(['tecnicos' => 'tecnico'])
        ->except(['show']);

    // Empresas cliente
    Route::resource('admin/empresas', EmpresaClienteController::class)
        ->names('admin.empresas')
        ->parameters(['empresas' => 'empresa'])
        ->except(['show']);
    Route::post('admin/empresas/{empresa}/restaurar', [EmpresaClienteController::class, 'restaurar'])
        ->name('admin.empresas.restaurar');

    // Marcas y modelos de vehículos
    Route::get('/admin/marcas',                              [MarcaModeloController::class, 'index'])->name('admin.marcas.index');
    Route::post('/admin/marcas',                             [MarcaModeloController::class, 'storeMarca'])->name('admin.marcas.store');
    Route::put('/admin/marcas/{marca}',                      [MarcaModeloController::class, 'updateMarca'])->name('admin.marcas.update');
    Route::delete('/admin/marcas/{marca}',                   [MarcaModeloController::class, 'destroyMarca'])->name('admin.marcas.destroy');
    Route::post('/admin/marcas/{marca}/modelos',             [MarcaModeloController::class, 'storeModelo'])->name('admin.modelos.store');
    Route::put('/admin/modelos/{modelo}',                    [MarcaModeloController::class, 'updateModelo'])->name('admin.modelos.update');
    Route::delete('/admin/modelos/{modelo}',                 [MarcaModeloController::class, 'destroyModelo'])->name('admin.modelos.destroy');

    // Días festivos
    Route::get('/admin/festivos',              [FestivoController::class, 'index'])->name('admin.festivos.index');
    Route::post('/admin/festivos',             [FestivoController::class, 'store'])->name('admin.festivos.store');
    Route::delete('/admin/festivos/{festivo}', [FestivoController::class, 'destroy'])->name('admin.festivos.destroy');
    Route::get('/admin/festivos/plantilla',    [FestivoController::class, 'plantilla'])->name('admin.festivos.plantilla');
    Route::post('/admin/festivos/importar',    [FestivoController::class, 'importar'])->name('admin.festivos.importar');

    // Migración Excel histórico
    Route::get('/admin/migracion',        [MigracionController::class, 'index'])->name('admin.migracion.index');
    Route::post('/admin/migracion/ejecutar', [MigracionController::class, 'ejecutar'])->name('admin.migracion.ejecutar');
});

// Recibo PDF de pago — visible también para el técnico dueño del pago
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,TECNICO'])->group(function () {
    Route::get('/pagos/{pago}/pdf', [LiquidacionController::class, 'pagoReciboPdf'])->name('pagos.pdf');
});

Route::middleware(['auth', 'role:ADMIN,COORDINADOR,TECNICO'])->group(function () {
    Route::get('/mis-tareas',                         [MisTareasController::class, 'index'])->name('mis-tareas.index');
    Route::get('/mis-tareas/historial',               [MisTareasController::class, 'historial'])->name('mis-tareas.historial');
    Route::get('/mis-tareas/{trabajo}/detalle',       [MisTareasController::class, 'detalle'])->name('mis-tareas.detalle');
    Route::get('/mis-tareas/{trabajo}/vehiculo',      [MisTareasController::class, 'vehiculo'])->name('mis-tareas.vehiculo');
    Route::post('/mis-tareas/{trabajo}/iniciar',      [MisTareasController::class, 'iniciar'])->name('mis-tareas.iniciar');
    Route::post('/mis-tareas/{trabajo}/detener',      [MisTareasController::class, 'detener'])->name('mis-tareas.detener');
    Route::post('/mis-tareas/{trabajo}/retomar',      [MisTareasController::class, 'retomar'])->name('mis-tareas.retomar');
    Route::post('/mis-tareas/{trabajo}/guardar',      [MisTareasController::class, 'guardar'])->name('mis-tareas.guardar');
    Route::post('/mis-tareas/{trabajo}/finalizar',    [MisTareasController::class, 'finalizar'])->name('mis-tareas.finalizar');
});

/*
|--------------------------------------------------------------------------
| Ruta de despliegue (hosting cPanel SIN SSH/terminal)
|--------------------------------------------------------------------------
| Permite ejecutar migraciones y limpiar caché por HTTP, ya que el hosting
| no tiene consola. Protegida con un token secreto en .env (DEPLOY_TOKEN).
|
| USO:  https://TU-DOMINIO/deploy/EL_TOKEN_SECRETO
|
| SEGURIDAD:
|  - Si DEPLOY_TOKEN no está definido en .env, la ruta responde 403.
|  - Nunca ejecuta comandos destructivos (no fresh/refresh/reset).
|  - Recomendado: borrar/comentar esta ruta cuando termines el despliegue.
*/
Route::get('/deploy/{token}', function (string $token) {
    $esperado = env('DEPLOY_TOKEN');

    abort_unless(is_string($esperado) && $esperado !== '' && hash_equals($esperado, $token), 403);

    $log = [];

    // 1) Migraciones pendientes (hacia adelante; --force es obligatorio en producción)
    Artisan::call('migrate', ['--force' => true]);
    $log['migrate'] = Artisan::output();

    // 2) Enlace simbólico de storage (ignora el error si ya existe)
    try {
        Artisan::call('storage:link');
        $log['storage:link'] = Artisan::output();
    } catch (\Throwable $e) {
        $log['storage:link'] = 'omitido: ' . $e->getMessage();
    }

    // 3) Limpiar caché de config, rutas y vistas (para que tome el código nuevo)
    Artisan::call('optimize:clear');
    $log['optimize:clear'] = Artisan::output();

    $salida = '';
    foreach ($log as $paso => $texto) {
        $salida .= "===== {$paso} =====\n" . trim($texto) . "\n\n";
    }

    return response('<pre>' . e($salida) . '</pre>')
        ->header('Content-Type', 'text/html; charset=utf-8');
})->name('deploy.run');

require __DIR__.'/auth.php';
