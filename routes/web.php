<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Redirigir raíz al dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard — accesible para todos los roles autenticados
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas placeholder para el sidebar (se implementan en fases posteriores)
Route::middleware(['auth', 'role:ADMIN,COORDINADOR,RECEPCION'])->group(function () {
    Route::get('/ordenes', fn() => redirect()->route('dashboard'))->name('ordenes.index');
});

Route::middleware(['auth', 'role:ADMIN,COORDINADOR'])->group(function () {
    Route::get('/torre', fn() => redirect()->route('dashboard'))->name('torre.index');
    Route::get('/cotizaciones', fn() => redirect()->route('dashboard'))->name('cotizaciones.index');
    Route::get('/catalogo', fn() => redirect()->route('dashboard'))->name('catalogo.index');
    Route::get('/liquidacion', fn() => redirect()->route('dashboard'))->name('liquidacion.index');
    Route::get('/produccion', fn() => redirect()->route('dashboard'))->name('produccion.index');
    Route::get('/admin', fn() => redirect()->route('dashboard'))->name('admin.index');
});

Route::middleware(['auth', 'role:TECNICO'])->group(function () {
    Route::get('/mis-tareas', fn() => redirect()->route('dashboard'))->name('mis-tareas.index');
});

require __DIR__.'/auth.php';
