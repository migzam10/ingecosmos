<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrdenTrabajoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\EmpresaClienteController;
use App\Http\Controllers\Admin\MigracionController;
use App\Http\Controllers\Admin\TecnicoAdminController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AsignarTecnicoController;
use App\Http\Controllers\EntregaParcialController;
use App\Http\Controllers\EstadoOTController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CatalogoMoController;
use App\Http\Controllers\LiquidacionController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\MisTareasController;
use App\Http\Controllers\FotoOtController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\TorreController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

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
    Route::get('/api/placa',       [OrdenTrabajoController::class, 'buscarPlaca'])->name('api.placa');
    Route::get('/api/modelos',     [OrdenTrabajoController::class, 'modelosPorMarca'])->name('api.modelos');
    Route::get('/api/catalogo-mo', [CatalogoMoController::class,  'porVehiculo'])->name('api.catalogo-mo');
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
    Route::get('/produccion',          [ProduccionController::class, 'index'])->name('produccion.index');
    Route::get('/produccion/exportar', [ProduccionController::class, 'exportar'])->name('produccion.exportar');
});

// Cotizaciones (COTIZADOR también puede gestionar cotizaciones)
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,COTIZADOR'])->group(function () {
    Route::get('/cotizaciones',                          [CotizacionController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/{cotizacion}',             [CotizacionController::class, 'show'])->name('cotizaciones.show');
    Route::get('/cotizaciones/{cotizacion}/pdf',         [CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');
    Route::get('/cotizaciones/{cotizacion}/editar',      [CotizacionController::class, 'edit'])->name('cotizaciones.edit');
    Route::put('/cotizaciones/{cotizacion}',             [CotizacionController::class, 'update'])->name('cotizaciones.update');
    Route::delete('/cotizaciones/{cotizacion}',          [CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');
    Route::get('/ordenes/{orden}/cotizar',               [CotizacionController::class, 'create'])->name('cotizaciones.create');
    Route::post('/ordenes/{orden}/cotizar',              [CotizacionController::class, 'store'])->name('cotizaciones.store');
});

// Torre de Control — COTIZADOR tiene acceso de solo lectura (filtros y semáforo, sin cambiar estados)
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,COTIZADOR'])->group(function () {
    Route::get('/torre', [TorreController::class, 'index'])->name('torre.index');
});

Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::resource('catalogo', CatalogoMoController::class)
        ->parameters(['catalogo' => 'catalogo'])
        ->except(['show']);
    Route::post('/catalogo/{catalogo}/restaurar', [CatalogoMoController::class, 'restaurar'])->name('catalogo.restaurar');
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

    // Migración Excel histórico
    Route::get('/admin/migracion',        [MigracionController::class, 'index'])->name('admin.migracion.index');
    Route::post('/admin/migracion/ejecutar', [MigracionController::class, 'ejecutar'])->name('admin.migracion.ejecutar');
});

Route::middleware(['auth', 'role:ADMIN,COORDINADOR,TECNICO'])->group(function () {
    Route::get('/mis-tareas',                         [MisTareasController::class, 'index'])->name('mis-tareas.index');
    Route::get('/mis-tareas/historial',               [MisTareasController::class, 'historial'])->name('mis-tareas.historial');
    Route::get('/mis-tareas/{trabajo}/detalle',       [MisTareasController::class, 'detalle'])->name('mis-tareas.detalle');
    Route::post('/mis-tareas/{trabajo}/iniciar',      [MisTareasController::class, 'iniciar'])->name('mis-tareas.iniciar');
    Route::post('/mis-tareas/{trabajo}/comentar',     [MisTareasController::class, 'comentar'])->name('mis-tareas.comentar');
    Route::post('/mis-tareas/{trabajo}/fotos',        [MisTareasController::class, 'subirFoto'])->name('mis-tareas.fotos');
    Route::post('/mis-tareas/{trabajo}/finalizar',    [MisTareasController::class, 'finalizar'])->name('mis-tareas.finalizar');
});

require __DIR__.'/auth.php';
