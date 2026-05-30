<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrdenTrabajoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AsignarTecnicoController;
use App\Http\Controllers\MisTareasController;
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
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,RECEPCION'])->group(function () {
    Route::resource('ordenes', OrdenTrabajoController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['ordenes' => 'orden']);
});

// AJAX endpoints
Route::middleware(['auth'])->group(function () {
    Route::get('/api/placa', [OrdenTrabajoController::class, 'buscarPlaca'])->name('api.placa');
    Route::get('/api/modelos', [OrdenTrabajoController::class, 'modelosPorMarca'])->name('api.modelos');
});

// Asignación de técnicos (Coordinador/Admin)
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::post('/ordenes/{orden}/tecnicos',           [AsignarTecnicoController::class, 'store'])->name('ordenes.tecnicos.store');
    Route::delete('/ordenes/{orden}/tecnicos/{trabajo}',[AsignarTecnicoController::class, 'destroy'])->name('ordenes.tecnicos.destroy');
});

// Placeholders fases futuras
Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::get('/torre', [TorreController::class, 'index'])->name('torre.index');
    Route::get('/cotizaciones', fn() => redirect()->route('dashboard'))->name('cotizaciones.index');
    Route::get('/catalogo',   fn() => redirect()->route('dashboard'))->name('catalogo.index');
    Route::get('/liquidacion', fn() => redirect()->route('dashboard'))->name('liquidacion.index');
    Route::get('/produccion', fn() => redirect()->route('dashboard'))->name('produccion.index');
    Route::get('/admin',      fn() => redirect()->route('dashboard'))->name('admin.index');
});

Route::middleware(['auth', 'role:ADMIN,COORDINADOR,TECNICO'])->group(function () {
    Route::get('/mis-tareas', [MisTareasController::class, 'index'])->name('mis-tareas.index');
    Route::post('/mis-tareas/{trabajo}/iniciar',   [MisTareasController::class, 'iniciar'])->name('mis-tareas.iniciar');
    Route::post('/mis-tareas/{trabajo}/comentar',  [MisTareasController::class, 'comentar'])->name('mis-tareas.comentar');
    Route::post('/mis-tareas/{trabajo}/finalizar', [MisTareasController::class, 'finalizar'])->name('mis-tareas.finalizar');
});

require __DIR__.'/auth.php';
