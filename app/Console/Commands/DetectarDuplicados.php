<?php

namespace App\Console\Commands;

use App\Models\Aprendiz;
use App\Models\JuicioEvaluativo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando: php artisan sena:detectar-duplicados
 *
 * Detecta inconsistencias en la base de datos:
 *   1. Aprendices con el mismo documento en diferentes fichas
 *   2. Juicios duplicados (mismo aprendiz + mismo resultado)
 *   3. Aprendices sin ningún juicio registrado
 *
 * Esencial para verificar la integridad de los datos después de importaciones.
 */
class DetectarDuplicados extends Command
{
    protected $signature   = 'sena:detectar-duplicados {--fix : Intenta corregir automáticamente los duplicados detectados}';
    protected $description = 'Detecta y reporta inconsistencias de datos en el sistema SENA.';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=yellow;options=bold>🔍 ANÁLISIS DE INTEGRIDAD DE DATOS — SENA</>');
        $this->newLine();

        $problemas = 0;

        // ── 1. DOCUMENTOS DUPLICADOS ────────────────────────────────────────
        $this->info('1. Verificando documentos duplicados en diferentes fichas...');

        $docsDuplicados = DB::table('aprendiz')
            ->select('Documento', DB::raw('COUNT(DISTINCT "Id_Ficha") as fichas_count'), DB::raw('STRING_AGG(CAST("Id_Ficha" AS VARCHAR), \', \') as fichas'))
            ->groupBy('Documento')
            ->havingRaw('COUNT(DISTINCT "Id_Ficha") > 1')
            ->get();

        if ($docsDuplicados->isEmpty()) {
            $this->line('   <fg=green>✓ Sin documentos duplicados en múltiples fichas.</>');
        } else {
            $problemas += $docsDuplicados->count();
            $this->warn("   ⚠ Se encontraron {$docsDuplicados->count()} documento(s) en múltiples fichas:");
            $rows = $docsDuplicados->map(fn ($d) => [
                $d->Documento, $d->fichas_count . ' fichas', $d->fichas
            ])->toArray();
            $this->table(['Documento', 'Aparece en', 'Fichas'], $rows);
        }

        // ── 2. JUICIOS DUPLICADOS ────────────────────────────────────────────
        $this->info('2. Verificando juicios evaluativos duplicados...');

        $juiciosDuplicados = DB::table('juicios_evaluativos')
            ->select('Id_Aprendiz', 'Id_Resultado', DB::raw('COUNT(*) as total'))
            ->groupBy('Id_Aprendiz', 'Id_Resultado')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($juiciosDuplicados->isEmpty()) {
            $this->line('   <fg=green>✓ Sin juicios duplicados detectados.</>');
        } else {
            $problemas += $juiciosDuplicados->count();
            $this->warn("   ⚠ Se encontraron {$juiciosDuplicados->count()} combinaciones aprendiz-resultado con múltiples juicios.");
            $this->line('   <fg=gray>   Ejecuta con --fix para conservar solo el más reciente.</>');

            if ($this->option('fix')) {
                $eliminados = 0;
                foreach ($juiciosDuplicados as $dup) {
                    // Conservar el más reciente, eliminar los anteriores
                    $ids = JuicioEvaluativo::where('Id_Aprendiz', $dup->Id_Aprendiz)
                        ->where('Id_Resultado', $dup->Id_Resultado)
                        ->orderByDesc('updated_at')
                        ->pluck('Id_Juicio')
                        ->skip(1); // Saltar el primero (más reciente)

                    JuicioEvaluativo::whereIn('Id_Juicio', $ids)->delete();
                    $eliminados += count($ids);
                }
                $this->line("   <fg=green>✓ Se eliminaron {$eliminados} juicios duplicados. Se conservó el más reciente de cada par.</>");
            }
        }

        // ── 3. APRENDICES SIN JUICIOS ────────────────────────────────────────
        $this->info('3. Verificando aprendices sin juicios registrados...');

        $sinJuicios = Aprendiz::doesntHave('juicios')->count();

        if ($sinJuicios === 0) {
            $this->line('   <fg=green>✓ Todos los aprendices tienen juicios registrados.</>');
        } else {
            $this->warn("   ⚠ {$sinJuicios} aprendice(s) no tienen ningún juicio registrado.");
            $this->line('   <fg=gray>   Puede indicar que el reporte de esos aprendices no ha sido importado todavía.</>');
        }

        // ── 4. COMPETENCIAS SIN RESULTADOS ──────────────────────────────────
        $this->info('4. Verificando competencias sin resultados asociados...');

        $compSinResultados = DB::table('competencia')
            ->leftJoin('resultados', 'competencia.Id_Competencia', '=', 'resultados.Id_Competencia')
            ->whereNull('resultados.Id_Resultado')
            ->count();

        if ($compSinResultados === 0) {
            $this->line('   <fg=green>✓ Todas las competencias tienen resultados de aprendizaje.</>');
        } else {
            $problemas++;
            $this->warn("   ⚠ {$compSinResultados} competencia(s) no tienen resultados de aprendizaje asociados.");
        }

        // ── RESUMEN ──────────────────────────────────────────────────────────
        $this->newLine();
        if ($problemas === 0) {
            $this->line('  <fg=green;options=bold>✓ Base de datos en buen estado. No se detectaron problemas.</>');
        } else {
            $this->line("  <fg=yellow;options=bold>⚠ Se detectaron {$problemas} tipo(s) de problema. Revisa los detalles arriba.</>");
        }

        $this->newLine();
        return $problemas > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
