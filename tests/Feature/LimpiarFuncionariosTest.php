<?php

namespace Tests\Feature;

use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\JuicioEvaluativo;
use App\Models\Programa;
use App\Models\Resultado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LimpiarFuncionariosTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $prog = Programa::create(['Nombre' => 'P', 'Modalidad' => 'P', 'Codigo' => '1', 'Version' => '1']);
        Ficha::create(['Id_Ficha' => 111, 'Jornada' => 'DIURNA', 'Id_Programa' => $prog->Id_Programa]);
        $ap = Aprendiz::create(['Tipo_Documento' => 'CC', 'Documento' => '1', 'Nombre' => 'A', 'Apellido' => 'B', 'Estado' => 'EN FORMACION', 'Id_Ficha' => 111]);
        $comp = Competencia::create(['Codigo' => 'C', 'Nombre' => 'c']);

        $real     = Funcionario::create(['Tipo_Documento' => 'CC', 'Documento' => 1234567890, 'Nombre' => 'JUAN GOMEZ', 'Apellido' => '']);
        $fecha    = Funcionario::create(['Tipo_Documento' => 'CC', 'Documento' => 40220261015, 'Nombre' => '04/02/2026 10.15 am', 'Apellido' => 'SENA']);
        $guion    = Funcionario::create(['Tipo_Documento' => 'CC', 'Documento' => 1000, 'Nombre' => '-', 'Apellido' => 'SENA']);

        foreach ([$real, $fecha, $guion] as $i => $f) {
            $res = Resultado::create(['Codigo' => "R{$i}", 'Nombre' => 'r', 'Id_Competencia' => $comp->Id_Competencia]);
            JuicioEvaluativo::create(['Id_Resultado' => $res->Id_Resultado, 'Id_Aprendiz' => $ap->Id_Aprendiz, 'Estado' => 0, 'Id_Funcionario' => $f->Id_Funcionario]);
        }

        return [$real, $fecha, $guion];
    }

    public function test_por_defecto_solo_simula(): void
    {
        $this->escenario();

        $this->artisan('sena:limpiar-funcionarios')->assertExitCode(0);

        $this->assertSame(3, Funcionario::count());
        $this->assertSame(0, JuicioEvaluativo::whereNull('Id_Funcionario')->count());
    }

    public function test_con_aplicar_borra_la_basura_y_desvincula_los_juicios(): void
    {
        [$real] = $this->escenario();

        $this->artisan('sena:limpiar-funcionarios', ['--aplicar' => true])->assertExitCode(0);

        $this->assertSame([$real->Id_Funcionario], Funcionario::pluck('Id_Funcionario')->all(), 'el funcionario real se conserva');
        $this->assertSame(2, JuicioEvaluativo::whereNull('Id_Funcionario')->count());
        $this->assertSame(3, JuicioEvaluativo::count(), 'ningún juicio se elimina');
    }

    public function test_sin_basura_no_hace_nada(): void
    {
        Funcionario::create(['Tipo_Documento' => 'CC', 'Documento' => 1234567890, 'Nombre' => 'JUAN GOMEZ', 'Apellido' => '']);

        $this->artisan('sena:limpiar-funcionarios', ['--aplicar' => true])->assertExitCode(0);
        $this->assertSame(1, Funcionario::count());
    }
}
