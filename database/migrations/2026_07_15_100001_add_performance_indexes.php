<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MEJORA TÉCNICA #1 — Índices de rendimiento
 *
 * Problema: Las tablas `aprendiz` y `juicios_evaluativos` no tienen índices
 * en columnas de búsqueda frecuente. Cada JOIN en el dashboard hace full scan.
 *
 * Solución: Agregar índices compuestos y simples para pasar de O(n) a O(log n).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Tabla: aprendiz ──────────────────────────────────────────────────
        Schema::table('aprendiz', function (Blueprint $table) {
            // Filtro más común: WHERE Id_Ficha = ?
            $table->index('Id_Ficha', 'idx_aprendiz_ficha');

            // Filtro de estado en listados y dashboard
            $table->index('Estado', 'idx_aprendiz_estado');

            // Búsqueda de texto por nombre/apellido
            $table->index('Nombre',   'idx_aprendiz_nombre');
            $table->index('Apellido', 'idx_aprendiz_apellido');

            // Combinado: ficha + estado (dashboard con filtro)
            $table->index(['Id_Ficha', 'Estado'], 'idx_aprendiz_ficha_estado');
        });

        // ── Tabla: juicios_evaluativos ────────────────────────────────────────
        Schema::table('juicios_evaluativos', function (Blueprint $table) {
            // JOIN más frecuente: juicios de un aprendiz
            $table->index('Id_Aprendiz', 'idx_juicios_aprendiz');

            // Conteo de aprobados/pendientes
            $table->index('Estado', 'idx_juicios_estado');

            // JOIN con resultados
            $table->index('Id_Resultado', 'idx_juicios_resultado');

            // Índice compuesto: aprendiz + estado (para el cálculo de porcentaje)
            $table->index(['Id_Aprendiz', 'Estado'], 'idx_juicios_aprendiz_estado');

            // Índice compuesto: resultado + aprendiz (clave de updateOrCreate)
            $table->index(['Id_Resultado', 'Id_Aprendiz'], 'idx_juicios_resultado_aprendiz');
        });

        // ── Tabla: resultados ─────────────────────────────────────────────────
        Schema::table('resultados', function (Blueprint $table) {
            $table->index('Id_Competencia', 'idx_resultados_competencia');
        });
    }

    public function down(): void
    {
        Schema::table('aprendiz', function (Blueprint $table) {
            $table->dropIndex('idx_aprendiz_ficha');
            $table->dropIndex('idx_aprendiz_estado');
            $table->dropIndex('idx_aprendiz_nombre');
            $table->dropIndex('idx_aprendiz_apellido');
            $table->dropIndex('idx_aprendiz_ficha_estado');
        });

        Schema::table('juicios_evaluativos', function (Blueprint $table) {
            $table->dropIndex('idx_juicios_aprendiz');
            $table->dropIndex('idx_juicios_estado');
            $table->dropIndex('idx_juicios_resultado');
            $table->dropIndex('idx_juicios_aprendiz_estado');
            $table->dropIndex('idx_juicios_resultado_aprendiz');
        });

        Schema::table('resultados', function (Blueprint $table) {
            $table->dropIndex('idx_resultados_competencia');
        });
    }
};
