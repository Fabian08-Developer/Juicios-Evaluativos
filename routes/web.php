<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AprendizController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\ImportacionController;
use Illuminate\Support\Facades\Route;
<<<<<<< Updated upstream
=======
use App\Http\Controllers\JuiciosController;
>>>>>>> Stashed changes

// ── AUTENTICACIÓN ─────────────────────────────────────────────────────────────
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── RUTAS PROTEGIDAS (requieren login) ────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard principal
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ⚡ Endpoint AJAX para actualización en tiempo real del dashboard
    Route::get('/api/dashboard-stats', [DashboardController::class, 'statsJson'])->name('dashboard.stats');

    // Rutas para Aprendices
    Route::get('/aprendices', [AprendizController::class, 'index'])->name('aprendices.index');
    Route::get('/aprendices/cargar', [AprendizController::class, 'showUploadForm'])->name('aprendices.upload');
    Route::post('/aprendices/importar', [AprendizController::class, 'import'])->name('aprendices.import');
    Route::post('/aprendices/importar/confirmar', [AprendizController::class, 'confirmarImportacion'])->name('aprendices.import.confirmar');
    Route::get('/aprendices/exportar-excel', [AprendizController::class, 'exportarExcel'])->name('aprendices.export.excel');
    Route::get('/aprendices/buscar', [AprendizController::class, 'buscarJson'])->name('aprendices.buscar');
    Route::get('/aprendices/{id}', [AprendizController::class, 'show'])->name('aprendices.show');
    Route::get('/aprendices/{id}/pdf', [AprendizController::class, 'exportarPdf'])->name('aprendices.pdf');

    // Rutas para Fichas
    Route::resource('fichas', FichaController::class)->except('show');
    Route::get('/fichas/{ficha}/historial', [ImportacionController::class, 'ficha'])->name('fichas.historial');

    // Rutas para Juicios
    Route::get('/juicios', [DashboardController::class, 'juiciosList'])->name('juicios.index');

    // Historial y Trazabilidad de importaciones
    Route::get('/importaciones', [ImportacionController::class, 'index'])->name('importaciones.index');
<<<<<<< Updated upstream
    Route::get('/importaciones/{importacion}', [ImportacionController::class, 'show'])->name('importaciones.show');
=======
    Route::get('/importaciones/comparar', [ImportacionController::class, 'comparar'])->name('importaciones.comparar');
    Route::get('/importaciones/{id}/json', [ImportacionController::class, 'showJson'])->name('importaciones.json');
>>>>>>> Stashed changes

});
