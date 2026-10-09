<?php

namespace Tests\Feature;

use App\Exports\AprendicesExport;
use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\Importacion;
use App\Models\JuicioEvaluativo;
use App\Models\Programa;
use App\Models\Resultado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Pantallas de consulta que siguen vigentes: dashboard, listado/búsqueda de
 * aprendices, exportación, listado de juicios e historial de importaciones.
 */
class ConsultasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ficha $ficha;
    private Competencia $comp;
    private Funcionario $func;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $programa = Programa::create(['Nombre' => 'ADSO', 'Modalidad' => 'PRESENCIAL', 'Codigo' => '228118', 'Version' => '1']);
        $this->ficha = Ficha::create(['Id_Ficha' => 2828282, 'Jornada' => 'DIURNA', 'Id_Programa' => $programa->Id_Programa]);
        $this->comp = Competencia::create(['Codigo' => 'C1', 'Nombre' => 'Competencia 1']);
        $this->func = Funcionario::create(['Tipo_Documento' => 'CC', 'Documento' => 12345678, 'Nombre' => 'JUAN GOMEZ', 'Apellido' => '']);
    }

    private function aprendiz(string $doc, string $nombre, string $apellido, int $aprobados, int $pendientes, string $estado = 'EN FORMACION'): Aprendiz
    {
        $ap = Aprendiz::create(['Tipo_Documento' => 'CC', 'Documento' => $doc, 'Nombre' => $nombre, 'Apellido' => $apellido, 'Estado' => $estado, 'Id_Ficha' => $this->ficha->Id_Ficha]);
        for ($i = 0; $i < $aprobados + $pendientes; $i++) {
            $res = Resultado::firstOrCreate(['Codigo' => "R{$i}"], ['Nombre' => "r{$i}", 'Id_Competencia' => $this->comp->Id_Competencia]);
            JuicioEvaluativo::create(['Id_Resultado' => $res->Id_Resultado, 'Id_Aprendiz' => $ap->Id_Aprendiz,
                'Estado' => $i < $aprobados ? 1 : 0, 'Id_Funcionario' => $this->func->Id_Funcionario]);
        }

        return $ap;
    }

    public function test_la_busqueda_ignora_mayusculas_en_lista_y_buscador_global(): void
    {
        $this->aprendiz('1000000001', 'JUAN', 'GARCIA', 1, 1);

        $lista = $this->actingAs($this->user)->get(route('aprendices.index', ['ficha' => 2828282, 'search' => 'garcia']));
        $this->assertSame(1, $lista->viewData('totalAprendices'));

        $global = $this->actingAs($this->user)->getJson(route('aprendices.buscar', ['q' => 'garcia']));
        $this->assertCount(1, $global->json());
    }

    public function test_la_exportacion_respeta_el_orden_pedido(): void
    {
        Excel::fake();
        $this->aprendiz('1000000001', 'ANA', 'X', 1, 1);

        $this->actingAs($this->user)->get(route('aprendices.export.excel', ['ficha' => 2828282, 'orden' => 'nombre_desc']));

        Excel::assertDownloaded('Aprendices_SENA_' . now()->format('Y-m-d') . '.xlsx', function (AprendicesExport $export) {
            return str_contains($export->query()->toSql(), 'order by "Nombre" desc');
        });
    }

    public function test_el_listado_de_juicios_carga_con_y_sin_funcionario(): void
    {
        // Antes: 500 "Call to a member function first() on null".
        $this->aprendiz('1000000001', 'A', 'B', 1, 1);
        JuicioEvaluativo::where('Estado', 0)->update(['Id_Funcionario' => null]);   // pendiente: Sofia no asigna funcionario

        $this->actingAs($this->user)->get(route('juicios.index'))
            ->assertOk()
            ->assertSee('JUAN GOMEZ');
    }

    public function test_el_listado_de_juicios_filtra_y_resume(): void
    {
        $this->aprendiz('1000000001', 'JUAN', 'GARCIA', 2, 1);
        $this->aprendiz('1000000002', 'ANA', 'LOPEZ', 0, 3);
        $otra = Ficha::create(['Id_Ficha' => 3142784, 'Jornada' => 'DIURNA', 'Id_Programa' => $this->ficha->Id_Programa]);
        $ap = $this->aprendiz('1000000003', 'LUIS', 'DIAZ', 1, 0);
        $ap->update(['Id_Ficha' => $otra->Id_Ficha]);

        $todos = $this->actingAs($this->user)->get(route('juicios.index'));
        $todos->assertOk();
        $this->assertSame([7, 3, 4], [$todos->viewData('totalJuicios'), $todos->viewData('totalAprobados'), $todos->viewData('totalPendientes')]);

        // La búsqueda no distingue mayúsculas; los indicadores siguen a la ficha y la búsqueda.
        $garcia = $this->actingAs($this->user)->get(route('juicios.index', ['buscar' => 'garcia']));
        $this->assertSame([3, 2], [$garcia->viewData('totalJuicios'), $garcia->viewData('totalAprobados')]);

        // El estado recorta la tabla pero no los indicadores.
        $pendientes = $this->actingAs($this->user)->get(route('juicios.index', ['ficha' => 2828282, 'estado' => '0']));
        $this->assertSame(6, $pendientes->viewData('totalJuicios'));
        $this->assertSame(4, $pendientes->viewData('juicios')->total());
        $this->assertTrue($pendientes->viewData('juicios')->every(fn ($j) => (int) $j->Estado === 0));
        $pendientes->assertDontSee('LUIS');
    }

    public function test_las_fichas_muestran_cuantos_aprendices_tienen(): void
    {
        $this->aprendiz('1000000001', 'A', 'B', 0, 1);
        $this->aprendiz('1000000002', 'C', 'D', 0, 1);
        Ficha::create(['Id_Ficha' => 3142784, 'Jornada' => 'DIURNA', 'Id_Programa' => $this->ficha->Id_Programa]);

        $r = $this->actingAs($this->user)->get(route('fichas.index'));
        $r->assertOk()->assertSee('2 aprendices')->assertSee('0 aprendices')
            ->assertSee(route('aprendices.index', ['ficha' => 2828282]), false);
    }

    public function test_el_historial_de_importaciones_distingue_advertencias_de_errores(): void
    {
        Importacion::create(['nombre_archivo' => 'a.xls', 'estado' => 'con_advertencias']);
        Importacion::create(['nombre_archivo' => 'b.xls', 'estado' => 'error']);

        $this->actingAs($this->user)->get(route('importaciones.index'))
            ->assertOk()->assertSee('Con advertencias')->assertSee('✗ Error');
    }

    public function test_las_pantallas_principales_cargan(): void
    {
        $ap = $this->aprendiz('1000000001', 'A', 'B', 1, 3);

        foreach (['/', '/dashboard', '/api/dashboard-stats', '/aprendices', '/aprendices/cargar', "/aprendices/{$ap->Id_Aprendiz}",
            "/aprendices/{$ap->Id_Aprendiz}/pdf", '/fichas', '/fichas/create', '/importaciones', '/juicios'] as $url) {
            $this->actingAs($this->user)->get($url)->assertOk();
        }
    }

    public function test_el_dashboard_no_carga_la_lista_completa_de_aprendices(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->aprendiz("10000000{$i}0", "N{$i}", 'X', 0, 4);
        }

        $vista = $this->actingAs($this->user)->get('/');
        $vista->assertOk();
        $this->assertCount(5, $vista->viewData('aprendicesRiesgoDetalle'), 'límite aplicado en SQL');
        $vista->assertViewMissing('aprendices');
    }

    // ── Módulos retirados ────────────────────────────────────────────────

    public function test_los_modulos_de_acciones_e_innovacion_ya_no_existen(): void
    {
        $ap = $this->aprendiz('1000000001', 'A', 'B', 1, 1);

        // Ni rutas...
        $retiradas = collect(Route::getRoutes())->map->getName()->filter(
            fn ($n) => str_starts_with((string) $n, 'acciones.') || str_starts_with((string) $n, 'remisiones.')
        );
        $this->assertCount(0, $retiradas, 'quedaron rutas de los módulos retirados: ' . $retiradas->implode(', '));

        foreach (['/acciones/matriz-evaluacion', '/acciones/diagnostico-desercion', '/acciones/cuellos-botella',
            "/acciones/simulador/{$ap->Id_Aprendiz}", '/remisiones', '/remisiones/oficio-pdf'] as $url) {
            $this->actingAs($this->user)->get($url)->assertNotFound();
        }

        // ...ni enlaces en el menú o en el expediente.
        $menu = $this->actingAs($this->user)->get('/')->getContent();
        foreach (['Acciones & Innovación', 'Matriz de Calificación', 'Semáforo de Deserción', 'Cuellos de Botella', 'Remisiones & Alertas'] as $texto) {
            $this->assertStringNotContainsString($texto, $menu);
        }
        $this->actingAs($this->user)->get("/aprendices/{$ap->Id_Aprendiz}")
            ->assertOk()->assertDontSee('Simular Plan de Salvación')->assertSee('Expediente PDF');
    }
}
