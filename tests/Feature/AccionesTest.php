<?php

namespace Tests\Feature;

use App\Exports\AprendicesExport;
use App\Mail\AlertaBienestarMail;
use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\JuicioEvaluativo;
use App\Models\Programa;
use App\Models\Remision;
use App\Models\Resultado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ficha $ficha;
    private Competencia $comp;
    private Funcionario $func;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['name' => 'Instructora Prueba']);
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

    // ── Alerta a Bienestar ────────────────────────────────────────────────

    public function test_el_score_guardado_en_la_remision_es_el_mismo_que_muestra_el_diagnostico(): void
    {
        config(['sena.bienestar_email' => null]);
        $ap = $this->aprendiz('1000000001', 'RIESGO', 'ALTO', 7, 18); // 72 % pendiente

        $pantalla = $this->actingAs($this->user)->get(route('acciones.diagnostico'))->viewData('aprendices')->first();
        $this->actingAs($this->user)->post(route('acciones.alerta-masiva'), ['aprendices_ids' => [$ap->Id_Aprendiz]]);

        $rem = Remision::first();
        $this->assertSame(85, $pantalla->score_riesgo);
        $this->assertSame($pantalla->score_riesgo, $rem->score_riesgo, 'antes: pantalla 85 / remisión 72');
        $this->assertSame('CRITICO', $rem->nivel_semaforo);
        $this->assertSame(18, $rem->total_pendientes);
    }

    public function test_sin_correo_de_bienestar_configurado_no_finge_haberlo_enviado(): void
    {
        Mail::fake();
        config(['sena.bienestar_email' => null]);
        $ap = $this->aprendiz('1000000001', 'A', 'B', 1, 4);

        $this->actingAs($this->user)->post(route('acciones.alerta-masiva'), ['aprendices_ids' => [$ap->Id_Aprendiz]])
            ->assertRedirect(route('remisiones.index'))->assertSessionMissing('success')->assertSessionHas('warning');

        $this->assertStringContainsString('NO se envió', session('warning'));
        $this->assertStringContainsString('BIENESTAR_EMAIL', session('warning'));
        Mail::assertNothingSent();
        $this->assertSame(1, Remision::count(), 'la remisión se registra igual');
    }

    public function test_con_correo_configurado_se_envia_al_destinatario_configurado(): void
    {
        Mail::fake();
        config(['sena.bienestar_email' => 'bienestar@centro.edu.co']);
        $ap = $this->aprendiz('1000000001', 'A', 'B', 1, 4);

        $this->actingAs($this->user)->post(route('acciones.alerta-masiva'), ['aprendices_ids' => [$ap->Id_Aprendiz]])
            ->assertSessionHas('success');

        Mail::assertSent(AlertaBienestarMail::class, fn ($m) => $m->hasTo('bienestar@centro.edu.co'));
        Mail::assertNotSent(AlertaBienestarMail::class, fn ($m) => $m->hasTo('leiderfabianramoscano99@gmail.com'));
    }

    public function test_si_el_envio_falla_se_informa_en_vez_de_aparentar_exito(): void
    {
        config(['sena.bienestar_email' => 'bienestar@centro.edu.co']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));
        $ap = $this->aprendiz('1000000001', 'A', 'B', 1, 4);

        $this->actingAs($this->user)->post(route('acciones.alerta-masiva'), ['aprendices_ids' => [$ap->Id_Aprendiz]])
            ->assertSessionMissing('success')->assertSessionHas('warning');

        $this->assertStringContainsString('NO se envió', session('warning'));
        $this->assertSame(1, Remision::count());
    }

    public function test_alerta_sin_seleccion_devuelve_error(): void
    {
        $this->actingAs($this->user)->post(route('acciones.alerta-masiva'), [])->assertSessionHas('error');
        $this->assertSame(0, Remision::count());
    }

    // ── Matriz de calificación ────────────────────────────────────────────

    public function test_la_matriz_registra_quien_califico_y_no_inventa_funcionario(): void
    {
        $ap = $this->aprendiz('1000000001', 'A', 'B', 0, 1);
        $juicio = JuicioEvaluativo::first();

        $this->actingAs($this->user)->postJson(route('acciones.matriz.actualizar'), [
            'id_aprendiz' => $ap->Id_Aprendiz, 'id_resultado' => $juicio->Id_Resultado, 'estado' => 1,
        ])->assertOk()->assertJson(['success' => true, 'estado' => 1]);

        $juicio->refresh();
        $this->assertSame($this->user->id, $juicio->registrado_por);
        $this->assertSame($this->func->Id_Funcionario, $juicio->Id_Funcionario, 'el funcionario oficial de Sofia no se pisa');
    }

    public function test_un_juicio_nuevo_desde_la_matriz_queda_sin_funcionario(): void
    {
        $ap = $this->aprendiz('1000000001', 'A', 'B', 0, 1);
        $res = Resultado::create(['Codigo' => 'NUEVO', 'Nombre' => 'n', 'Id_Competencia' => $this->comp->Id_Competencia]);

        $this->actingAs($this->user)->postJson(route('acciones.matriz.actualizar'), [
            'id_aprendiz' => $ap->Id_Aprendiz, 'id_resultado' => $res->Id_Resultado, 'estado' => 1,
        ])->assertOk();

        $nuevo = JuicioEvaluativo::where('Id_Resultado', $res->Id_Resultado)->first();
        $this->assertNull($nuevo->Id_Funcionario);
        $this->assertSame($this->user->id, $nuevo->registrado_por);
    }

    public function test_guardar_en_lote_invalida_la_cache_de_la_ficha_afectada(): void
    {
        $ap = $this->aprendiz('1000000001', 'A', 'B', 0, 2);
        $otra = Ficha::create(['Id_Ficha' => 111, 'Jornada' => 'DIURNA', 'Id_Programa' => $this->ficha->Id_Programa]);
        Cache::put('dashboard.stats.global', ['x'], 300);
        Cache::put('dashboard.stats.ficha.2828282', ['x'], 300);
        Cache::put('dashboard.stats.ficha.111', ['x'], 300);
        $res = Resultado::first();

        $this->actingAs($this->user)->postJson(route('acciones.matriz.lote'), [
            'cambios' => [['id_aprendiz' => $ap->Id_Aprendiz, 'id_resultado' => $res->Id_Resultado, 'estado' => 1]],
        ])->assertOk();

        $this->assertFalse(Cache::has('dashboard.stats.global'));
        $this->assertFalse(Cache::has('dashboard.stats.ficha.2828282'), 'antes quedaba obsoleta hasta 5 min');
        $this->assertTrue(Cache::has('dashboard.stats.ficha.111'), 'la ficha no afectada conserva su caché');
    }

    public function test_el_lote_rechaza_ids_inexistentes(): void
    {
        $this->actingAs($this->user)->postJson(route('acciones.matriz.lote'), [
            'cambios' => [['id_aprendiz' => 9999, 'id_resultado' => 9999, 'estado' => 1]],
        ])->assertStatus(422);
    }

    // ── Búsqueda y exportación ────────────────────────────────────────────

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

    // ── Pantallas ─────────────────────────────────────────────────────────

    public function test_el_listado_de_juicios_carga_con_funcionario_local_o_sin_el(): void
    {
        $this->aprendiz('1000000001', 'A', 'B', 1, 1);
        $local = JuicioEvaluativo::where('Estado', 0)->first();
        $local->update(['Id_Funcionario' => null, 'registrado_por' => $this->user->id]);

        // Antes: 500 "Call to a member function first() on null".
        $this->actingAs($this->user)->get(route('juicios.index'))
            ->assertOk()
            ->assertSee('JUAN GOMEZ')
            ->assertSee('Instructora Prueba');
    }

    public function test_el_historial_de_importaciones_distingue_advertencias_de_errores(): void
    {
        \App\Models\Importacion::create(['nombre_archivo' => 'a.xls', 'estado' => 'con_advertencias']);
        \App\Models\Importacion::create(['nombre_archivo' => 'b.xls', 'estado' => 'error']);

        $this->actingAs($this->user)->get(route('importaciones.index'))
            ->assertOk()->assertSee('Con advertencias')->assertSee('✗ Error');
    }

    public function test_las_pantallas_principales_cargan(): void
    {
        $ap = $this->aprendiz('1000000001', 'A', 'B', 1, 3);

        foreach (['/', '/dashboard', '/api/dashboard-stats', '/aprendices', '/aprendices/cargar', "/aprendices/{$ap->Id_Aprendiz}",
            '/fichas', '/importaciones', '/remisiones', '/acciones/cuellos-botella', '/acciones/diagnostico-desercion',
            '/acciones/matriz-evaluacion', "/acciones/simulador/{$ap->Id_Aprendiz}"] as $url) {
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
}
