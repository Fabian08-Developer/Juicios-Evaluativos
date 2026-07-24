<?php

namespace App\Console\Commands;

use App\Models\Aprendiz;
use App\Models\Importacion;
use App\Models\JuicioEvaluativo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * MEJORA TÉCNICA #6 — Artisan Command Personalizado
 *
 * Comando: php artisan sena:reporte-general [--ficha=XXXXX]
 *
 * Genera un reporte estadístico completo en la terminal.
 * Útil para:
 *   - Auditoría rápida sin abrir el navegador
 *   - Verificación de integridad de datos
 *   - Debugging de producción (acceso SSH)
 *   - Presentación técnica del proyecto
 */
class ReporteGeneral extends Command
{
    protected $signature   = 'sena:reporte-general {--ficha= : Filtrar por número de ficha}';
    protected $description = 'Genera un reporte estadístico general del sistema SENA en la terminal.';

    public function handle(): int
    {
        $fichaId = $this->option('ficha');

        $this->newLine();
        $this->line('  <fg=green;options=bold>╔══════════════════════════════════════════════╗</>');
        $this->line('  <fg=green;options=bold>║   SENA — Gestión de Juicios Evaluativos      ║</>');
        $this->line('  <fg=green;options=bold>║   Reporte General del Sistema                ║</>');
        $this->line('  <fg=green;options=bold>╚══════════════════════════════════════════════╝</>');
        $this->newLine();

        if ($fichaId) {
            $this->line("  <fg=yellow>Filtro activo: Ficha <options=bold>{$fichaId}</></fg=yellow>");
            $this->newLine();
        }

        // ── APRENDICES ──────────────────────────────────────────────────────
        $this->info('📋 APRENDICES');

        $qAprendiz = Aprendiz::query()->when($fichaId, fn ($q) => $q->deFicha($fichaId));

        $totalAprendices  = (clone $qAprendiz)->count();
        $enFormacion      = (clone $qAprendiz)->enFormacion()->count();
        $retiroVoluntario = (clone $qAprendiz)->enRetiro()->count();
        $trasladados      = (clone $qAprendiz)->trasladados()->count();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total Aprendices',    "<fg=cyan;options=bold>{$totalAprendices}</>"],
                ['En Formación',        "<fg=green>{$enFormacion}</>"],
                ['Retiro Voluntario',   "<fg=yellow>{$retiroVoluntario}</>"],
                ['Trasladados',         "<fg=gray>{$trasladados}</>"],
            ]
        );

        // ── JUICIOS ─────────────────────────────────────────────────────────
        $this->info('⚖️  JUICIOS EVALUATIVOS');

        $qJuicios = JuicioEvaluativo::query();
        if ($fichaId) {
            $qJuicios->whereHas('aprendiz', fn ($q) => $q->where('Id_Ficha', $fichaId));
        }

        $totalJuicios  = (clone $qJuicios)->count();
        $aprobados     = (clone $qJuicios)->where('Estado', 1)->count();
        $pendientes    = (clone $qJuicios)->where('Estado', 0)->count();
        $tasaAprobacion = $totalJuicios > 0 ? round(($aprobados / $totalJuicios) * 100, 1) : 0;

        $colorTasa = $tasaAprobacion >= 80 ? 'green' : ($tasaAprobacion >= 50 ? 'yellow' : 'red');

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total Juicios',      "<fg=cyan;options=bold>{$totalJuicios}</>"],
                ['Aprobados',          "<fg=green>{$aprobados}</>"],
                ['Pendientes',         "<fg=yellow>{$pendientes}</>"],
                ['Tasa de Aprobación', "<fg={$colorTasa};options=bold>{$tasaAprobacion}%</>"],
            ]
        );

        // ── APRENDICES EN RIESGO ────────────────────────────────────────────
        $enRiesgo = Aprendiz::enRiesgo()
            ->when($fichaId, fn ($q) => $q->deFicha($fichaId))
            ->get();

        if ($enRiesgo->isNotEmpty()) {
            $this->newLine();
            $this->warn("⚠️  APRENDICES EN RIESGO ({$enRiesgo->count()})");

            $rows = $enRiesgo->map(function ($a) {
                $total     = $a->juicios()->count();
                $pendientes = $a->juicios()->where('Estado', 0)->count();
                $pct       = $total > 0 ? round(($pendientes / $total) * 100) : 0;
                return [
                    "{$a->Nombre} {$a->Apellido}",
                    $a->Documento,
                    $a->Id_Ficha,
                    "{$pendientes}/{$total}",
                    "{$pct}% pend.",
                ];
            })->toArray();

            $this->table(['Nombre', 'Documento', 'Ficha', 'Pendientes', 'Riesgo'], $rows);
        } else {
            $this->line('  <fg=green>✓ No hay aprendices en riesgo.</>');
        }

        // ── HISTORIAL DE IMPORTACIONES ──────────────────────────────────────
        $this->newLine();
        $this->info('📥 HISTORIAL DE IMPORTACIONES (últimas 5)');

        $importaciones = Importacion::latest()->limit(5)->get();

        if ($importaciones->isEmpty()) {
            $this->line('  <fg=gray>Sin importaciones registradas todavía.</>');
        } else {
            $rows = $importaciones->map(fn ($imp) => [
                $imp->created_at->format('d/m/Y H:i'),
                $imp->nombre_archivo,
                $imp->id_ficha ?? '—',
                $imp->aprendices_procesados,
                $imp->estado === 'exitoso' ? '<fg=green>✓ Exitoso</>' : '<fg=red>✗ Error</>',
            ])->toArray();

            $this->table(['Fecha', 'Archivo', 'Ficha', 'Procesados', 'Estado'], $rows);
        }

        $this->newLine();
        $this->line('  <fg=gray>Generado: ' . now()->format('d/m/Y H:i:s') . '</>');
        $this->newLine();

        return Command::SUCCESS;
    }
}
