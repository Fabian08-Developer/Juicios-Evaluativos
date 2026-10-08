<?php

namespace App\Console\Commands;

use App\Models\Funcionario;
use App\Models\JuicioEvaluativo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando: php artisan sena:limpiar-funcionarios [--aplicar]
 *
 * El importador anterior leía el funcionario de una columna fija. Cuando esa
 * columna era la fecha (reportes con una columna extra), creaba "funcionarios"
 * cuyo nombre era una fecha («04/02/2026 10.15 am») y cuyo documento eran los
 * dígitos de esa fecha. Para los juicios pendientes («-») inventaba además un
 * funcionario con documento 1000. Este comando los localiza y, con --aplicar,
 * deja esos juicios sin funcionario y borra los registros basura.
 *
 * Por defecto solo muestra qué haría (simulación).
 */
class LimpiarFuncionariosInvalidos extends Command
{
    protected $signature   = 'sena:limpiar-funcionarios {--aplicar : Ejecuta la limpieza (sin esta opción solo se muestra el resultado esperado)}';
    protected $description = 'Elimina funcionarios inválidos creados por el importador anterior (fechas o "-" como nombre).';

    public function handle(): int
    {
        $basura = Funcionario::all()->filter(fn (Funcionario $f) => $this->esBasura($f));

        if ($basura->isEmpty()) {
            $this->info('✓ No hay funcionarios inválidos.');
            return self::SUCCESS;
        }

        $ids        = $basura->pluck('Id_Funcionario');
        $conJuicios = JuicioEvaluativo::whereIn('Id_Funcionario', $ids)->count();

        $this->warn("Funcionarios inválidos encontrados: {$basura->count()} (con {$conJuicios} juicios asociados).");
        $this->table(
            ['Id', 'Documento', 'Nombre (recortado)'],
            $basura->take(10)->map(fn ($f) => [$f->Id_Funcionario, $f->Documento, mb_substr((string) $f->Nombre, 0, 30)])->all()
        );

        if (! $this->option('aplicar')) {
            $this->line('Simulación: no se cambió nada. Ejecuta con --aplicar para limpiar.');
            $this->line('Tip: vuelve a importar el último reporte de cada ficha para recuperar los funcionarios correctos de los juicios.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            JuicioEvaluativo::whereIn('Id_Funcionario', $ids)->update(['Id_Funcionario' => null]);
            Funcionario::whereIn('Id_Funcionario', $ids)->delete();
        });

        $this->info("✓ Limpieza aplicada: {$basura->count()} funcionarios eliminados; {$conJuicios} juicios quedaron sin funcionario.");
        $this->line('Vuelve a importar el último reporte de cada ficha para reasignar los funcionarios reales.');

        return self::SUCCESS;
    }

    private function esBasura(Funcionario $f): bool
    {
        $nombre = trim((string) $f->Nombre);

        return $nombre === ''
            || $nombre === '-'
            || (string) $f->Documento === '1000'                         // documento de relleno del importador anterior
            || preg_match('#^\d{1,2}/\d{1,2}/\d{4}#', $nombre) === 1;    // el "nombre" es una fecha
    }
}
