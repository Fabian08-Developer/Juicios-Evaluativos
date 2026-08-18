<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AprendizController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\ImportacionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JuiciosController;
use App\Http\Controllers\InnovacionAcademicaController;

// Dashboard principal
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);

// ⚡ Endpoint AJAX para actualización en tiempo real del dashboard
Route::get('/api/dashboard-stats', [DashboardController::class, 'statsJson'])->name('dashboard.stats');

// Rutas para Aprendices
Route::get('/aprendices', [AprendizController::class, 'index'])->name('aprendices.index');
Route::get('/aprendices/cargar', [AprendizController::class, 'showUploadForm'])->name('aprendices.upload');
Route::post('/aprendices/importar', [AprendizController::class, 'import'])->name('aprendices.import');
Route::get('/aprendices/exportar-excel', [AprendizController::class, 'exportarExcel'])->name('aprendices.export.excel');
Route::get('/aprendices/buscar', [AprendizController::class, 'buscarJson'])->name('aprendices.buscar');
Route::get('/aprendices/{id}', [AprendizController::class, 'show'])->name('aprendices.show');
Route::get('/aprendices/{id}/pdf', [AprendizController::class, 'exportarPdf'])->name('aprendices.pdf');

// Rutas para Fichas
Route::resource('fichas', FichaController::class);

// Rutas para Juicios
Route::get('/juicios', [DashboardController::class, 'juiciosList'])->name('juicios.index');

// Historial de importaciones
Route::get('/importaciones', [ImportacionController::class, 'index'])->name('importaciones.index');

// ── SUITE DE ACCIONES E INNOVACIÓN ACADÉMICA ──────────────────────────────
Route::prefix('acciones')->name('acciones.')->group(function () {
    // 1. Simulador de Salvación y Acta de Mejoramiento
    Route::get('/simulador/{id}', [InnovacionAcademicaController::class, 'simularSalvacion'])->name('simulador');
    Route::get('/simulador/{id}/acta-pdf', [InnovacionAcademicaController::class, 'descargarActaPdf'])->name('acta.pdf');

    // 2. Cuellos de Botella y Grupo de Refuerzo
    Route::get('/cuellos-botella', [InnovacionAcademicaController::class, 'cuellosBotella'])->name('cuellos-botella');

    // 3. Semáforo Predictivo de Deserción
    Route::get('/diagnostico-desercion', [InnovacionAcademicaController::class, 'diagnosticoDesercion'])->name('diagnostico');
    Route::post('/diagnostico-desercion/alerta-masiva', [InnovacionAcademicaController::class, 'alertaMasiva'])->name('alerta-masiva');

    // 4. Matriz Interactiva de Evaluación Rápida
    Route::get('/matriz-evaluacion', [InnovacionAcademicaController::class, 'matrizEvaluacion'])->name('matriz');
    Route::post('/matriz-evaluacion/actualizar', [InnovacionAcademicaController::class, 'actualizarJuicioAjax'])->name('matriz.actualizar');
    Route::post('/matriz-evaluacion/guardar-lote', [InnovacionAcademicaController::class, 'guardarLoteAjax'])->name('matriz.lote');

    // 5. Centro de Notificaciones y Bitácora
    Route::post('/notificar-aprendiz', [InnovacionAcademicaController::class, 'registrarNotificacion'])->name('notificar');
});

// ── BANDEJA Y GESTIÓN DE REMISIONES A BIENESTAR ─────────────────────────────
Route::get('/remisiones', [InnovacionAcademicaController::class, 'historialRemisiones'])->name('remisiones.index');
Route::post('/remisiones/{id}/estado', [InnovacionAcademicaController::class, 'actualizarEstadoRemision'])->name('remisiones.estado');
Route::get('/remisiones/oficio-pdf', [InnovacionAcademicaController::class, 'descargarOficioPdf'])->name('remisiones.oficio-pdf');

