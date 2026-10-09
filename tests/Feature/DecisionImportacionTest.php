<?php

namespace Tests\Feature;

use App\Models\Aprendiz;
use App\Models\Importacion;
use App\Models\ImportacionCambio;
use App\Models\JuicioEvaluativo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaReporteSofia;
use Tests\TestCase;

/**
 * «Revisa antes de aplicar»: un reporte que desharía aprobaciones o trae
 * aprendices de otra ficha no se aplica hasta que el usuario decide.
 */
class DecisionImportacionTest extends TestCase
{
    use RefreshDatabase, CreaReporteSofia;

    private const A = '1000000001';
    private const B = '1000000002';
    private const RAP1 = '593147 - 01 RESULTADO UNO';
    private const RAP2 = '593148 - 02 RESULTADO DOS';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function importar(array $filas, array $opts = [])
    {
        return $this->actingAs($this->user)
            ->post(route('aprendices.import'), ['archivo_excel' => $this->reporte($filas, $opts)]);
    }

    /** Fila de un aprendiz para un RAP, aprobada o por evaluar. */
    private function juicio(string $doc, string $rap, bool $aprobado, array $extra = []): array
    {
        return $this->fila(array_merge([
            'doc'    => $doc,
            'nombre' => 'APRENDIZ ' . substr($doc, -1),
            'rap'    => $rap,
            'juicio' => $aprobado ? 'APROBADO' : 'POR EVALUAR',
            'fecha'  => $aprobado ? '04/02/2026 10.15 am' : '',
            'func'   => $aprobado ? 'CC 1234567890 - JUAN GOMEZ' : '-',
        ], $extra));
    }

    private function estado(string $doc, string $rap): int
    {
        return (int) JuicioEvaluativo::whereHas('aprendiz', fn ($q) => $q->where('Documento', $doc))
            ->whereHas('resultado', fn ($q) => $q->where('Codigo', explode(' - ', $rap)[0]))
            ->value('Estado');
    }

    private function cambios(string $tipo): int
    {
        return ImportacionCambio::where('importacion_id', Importacion::max('id'))->where('tipo', $tipo)->count();
    }

    /** A aprobó los dos RAP; B no aprobó nada. */
    private function cargaReciente(): void
    {
        $this->importar([
            $this->juicio(self::A, self::RAP1, false), $this->juicio(self::A, self::RAP2, false),
            $this->juicio(self::B, self::RAP1, false), $this->juicio(self::B, self::RAP2, false),
        ]);
        $this->importar([
            $this->juicio(self::A, self::RAP1, true), $this->juicio(self::A, self::RAP2, true),
            $this->juicio(self::B, self::RAP1, false), $this->juicio(self::B, self::RAP2, false),
        ]);
    }

    /** Reporte viejo para A (RAP2 por evaluar) pero con un avance real de B (RAP1 aprobado). */
    private function reporteMezclado()
    {
        return $this->importar([
            $this->juicio(self::A, self::RAP1, true), $this->juicio(self::A, self::RAP2, false),
            $this->juicio(self::B, self::RAP1, true), $this->juicio(self::B, self::RAP2, false),
        ]);
    }

    public function test_un_reporte_que_desharia_aprobaciones_no_se_aplica_hasta_decidir(): void
    {
        $this->cargaReciente();
        $importaciones = Importacion::count();

        $carga = $this->reporteMezclado();

        $carga->assertRedirectContains('/aprendices/importar/');
        $this->assertSame(1, $this->estado(self::A, self::RAP2), 'nada se aplica todavía');
        $this->assertSame(0, $this->estado(self::B, self::RAP1), 'ni siquiera los avances');
        $this->assertSame($importaciones, Importacion::count(), 'la carga pendiente no queda en el historial');
        $this->assertCount(1, Storage::disk('local')->files('importaciones-pendientes'));

        $this->actingAs($this->user)->get($carga->headers->get('Location'))
            ->assertOk()
            ->assertSee('Revisa antes de aplicar')
            ->assertSee('APRENDIZ 1')->assertSee('593148')
            ->assertSee('Conservar los aprobados')->assertSee('Aplicar el reporte tal cual')->assertSee('Cancelar')
            ->assertDontSee('Trasladar a la ficha');
    }

    public function test_preservar_conserva_los_aprobados_y_aplica_los_avances(): void
    {
        $this->cargaReciente();

        $this->decidir($this->reporteMezclado(), 'preservar')->assertSessionHas('success');

        $this->assertSame(1, $this->estado(self::A, self::RAP2), 'el aprobado se conserva');
        $this->assertSame(1, $this->estado(self::B, self::RAP1), 'el avance de B sí se aplica');
        $this->assertSame(1, $this->cambios(ImportacionCambio::JUICIO_PROTEGIDO));
        $this->assertSame(1, $this->cambios(ImportacionCambio::JUICIO_APROBADO));
        $this->assertSame(0, $this->cambios(ImportacionCambio::JUICIO_REVERTIDO));
        $this->assertStringContainsString('Se conservaron 1 juicio(s) aprobados', session('success'));

        $imp = Importacion::orderByDesc('id')->first();
        $this->assertSame('preservar', $imp->resumen['politica']);
        $this->assertSame($this->user->id, $imp->user_id);
        $this->assertCount(0, Storage::disk('local')->allFiles(), 'el archivo temporal se borra');

        $this->actingAs($this->user)->get(route('importaciones.show', $imp))
            ->assertOk()->assertSee('Aplicado con tu decisión')->assertSee('Aprobados protegidos');
        $this->actingAs($this->user)->get(route('importaciones.index'))->assertSee('1 protegidos');
    }

    public function test_forzar_aplica_el_reporte_tal_cual(): void
    {
        $this->cargaReciente();

        $this->decidir($this->reporteMezclado(), 'forzar');

        $this->assertSame(0, $this->estado(self::A, self::RAP2));
        $this->assertSame(1, $this->estado(self::B, self::RAP1));
        $this->assertSame(1, $this->cambios(ImportacionCambio::JUICIO_REVERTIDO));
        $this->assertSame(0, $this->cambios(ImportacionCambio::JUICIO_PROTEGIDO));
    }

    public function test_cancelar_no_modifica_nada_y_borra_el_archivo(): void
    {
        $this->cargaReciente();
        $importaciones = Importacion::count();

        $this->decidir($this->reporteMezclado(), 'cancelar')
            ->assertRedirect(route('aprendices.upload'))
            ->assertSessionHas('success');

        $this->assertStringContainsString('cancelada', session('success'));
        $this->assertSame(1, $this->estado(self::A, self::RAP2));
        $this->assertSame(0, $this->estado(self::B, self::RAP1));
        $this->assertSame($importaciones, Importacion::count());
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_la_decision_se_aplica_una_sola_vez(): void
    {
        $this->cargaReciente();
        $carga = $this->reporteMezclado();
        $importaciones = Importacion::count();

        $this->decidir($carga, 'preservar');
        $this->decidir($carga, 'forzar')->assertRedirect(route('aprendices.upload'))->assertSessionHas('error');

        $this->assertSame($importaciones + 1, Importacion::count());
        $this->assertSame(1, $this->estado(self::A, self::RAP2), 'el segundo envío no aplicó «forzar»');
    }

    public function test_una_accion_desconocida_o_un_token_ajeno_no_aplican_nada(): void
    {
        $this->cargaReciente();
        $carga = $this->reporteMezclado();

        $this->decidir($carga, 'borrar-todo')->assertSessionHasErrors('accion');
        $this->assertCount(1, Storage::disk('local')->files('importaciones-pendientes'), 'sigue pendiente');

        // Un token que no es de esta sesión (u otro usuario) no existe para ella.
        $ajeno = route('aprendices.import.decision', '9b2f6c1e-3a4b-4c5d-8e9f-0a1b2c3d4e5f');
        $this->actingAs($this->user)->get($ajeno)->assertRedirect(route('aprendices.upload'))->assertSessionHas('error');
        $this->actingAs($this->user)->post($ajeno, ['accion' => 'forzar'])->assertSessionHas('error');
        $this->actingAs($this->user)->get('/aprendices/importar/no-es-un-token')->assertNotFound();

        $this->assertSame(1, $this->estado(self::A, self::RAP2));
    }

    public function test_aprendiz_de_otra_ficha_preservar_lo_deja_en_su_ficha(): void
    {
        $this->importar([$this->juicio(self::A, self::RAP1, true)], ['ficha' => '3142784']);

        // La otra ficha lo trae con otro estado y un RAP nuevo.
        $carga = $this->importar([
            $this->juicio(self::A, self::RAP2, true, ['estado' => 'TRASLADADO', 'nombre' => 'NOMBRE CORREGIDO']),
        ], ['ficha' => '2828282']);

        $this->actingAs($this->user)->get($carga->headers->get('Location'))
            ->assertOk()->assertSee('Aprendices que hoy están en otra ficha')->assertSee('Trasladar a la ficha 2828282')
            ->assertDontSee('Aplicar el reporte tal cual');

        $this->decidir($carga, 'preservar');

        $a = Aprendiz::where('Documento', self::A)->first();
        $this->assertSame(3142784, (int) $a->Id_Ficha, 'sigue en su ficha');
        $this->assertSame('EN FORMACION', $a->Estado, 'el estado es el de su ficha, no el del otro reporte');
        $this->assertSame('NOMBRE CORREGIDO', $a->Nombre);
        $this->assertSame(1, $this->estado(self::A, self::RAP2), 'sus juicios del otro reporte se registran');
        $this->assertSame(1, $this->cambios(ImportacionCambio::APRENDIZ_NO_TRASLADADO));
        $this->assertSame(0, $this->cambios(ImportacionCambio::APRENDIZ_MOVIDO));
    }

    public function test_trasladar_mueve_al_aprendiz_y_conserva_sus_aprobados(): void
    {
        $this->importar([$this->juicio(self::A, self::RAP1, true)], ['ficha' => '3142784']);
        $carga = $this->importar([$this->juicio(self::A, self::RAP1, false)], ['ficha' => '2828282']);

        // Desharía una aprobación y lo sacaría de su ficha: las cuatro opciones.
        $this->actingAs($this->user)->get($carga->headers->get('Location'))
            ->assertOk()->assertSee('Conservar los aprobados y dejar a cada aprendiz en su ficha')
            ->assertSee('Trasladar a la ficha 2828282 (conservando los aprobados)')
            ->assertSee('Aplicar el reporte tal cual');

        $this->decidir($carga, 'trasladar');

        $this->assertSame(2828282, (int) Aprendiz::where('Documento', self::A)->value('Id_Ficha'));
        $this->assertSame(1, $this->estado(self::A, self::RAP1));
        $this->assertSame(1, $this->cambios(ImportacionCambio::APRENDIZ_MOVIDO));
        $this->assertSame(1, $this->cambios(ImportacionCambio::JUICIO_PROTEGIDO));
    }

    public function test_los_archivos_pendientes_abandonados_se_borran_al_dia(): void
    {
        $disco = Storage::disk('local');
        $disco->put('importaciones-pendientes/viejo.xls', 'x');
        touch($disco->path('importaciones-pendientes/viejo.xls'), now()->subDays(2)->getTimestamp());
        $disco->put('importaciones-pendientes/reciente.xls', 'x');

        $this->cargaReciente();
        $abandonada = $this->reporteMezclado();
        $token = basename($abandonada->headers->get('Location'));
        touch($disco->path("importaciones-pendientes/{$token}.xls"), now()->subDays(2)->getTimestamp());

        $this->reporteMezclado();

        $this->assertFalse($disco->exists('importaciones-pendientes/viejo.xls'));
        $this->assertFalse($disco->exists("importaciones-pendientes/{$token}.xls"));
        $this->assertTrue($disco->exists('importaciones-pendientes/reciente.xls'));
        $this->assertCount(2, $disco->files('importaciones-pendientes'));
        $this->assertArrayNotHasKey($token, session('importacion_pendiente'), 'la sesión también la olvida');
        $this->actingAs($this->user)->get($abandonada->headers->get('Location'))->assertSessionHas('error');
    }
}
