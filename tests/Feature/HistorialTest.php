<?php

namespace Tests\Feature;

use App\Models\Aprendiz;
use App\Models\Importacion;
use App\Models\ImportacionCambio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaReporteSofia;
use Tests\TestCase;

/**
 * Historial entre reportes: qué cambió frente a la carga anterior de la ficha.
 */
class HistorialTest extends TestCase
{
    use RefreshDatabase, CreaReporteSofia;

    private const A = '1000000001';
    private const B = '1000000002';
    private const C = '1000000003';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Filas de un reporte: cada aprendiz con dos RAP (593147 y 593148).
     *
     * @param  array<int,string>  $aprobados  "documento:rap" aprobados
     * @param  array<string,string>  $estados  documento => estado del aprendiz
     */
    private function filas(array $aprobados = [], array $estados = [], array $docs = [self::A, self::B]): array
    {
        $filas = [];
        foreach ($docs as $doc) {
            foreach (['593147 - 01 RESULTADO UNO', '593148 - 02 RESULTADO DOS'] as $rap) {
                $aprobado = in_array($doc . ':' . explode(' - ', $rap)[0], $aprobados, true);
                $filas[] = $this->fila([
                    'doc'    => $doc,
                    'nombre' => 'APRENDIZ ' . substr($doc, -1),
                    'rap'    => $rap,
                    'estado' => $estados[$doc] ?? 'EN FORMACION',
                    'juicio' => $aprobado ? 'APROBADO' : 'POR EVALUAR',
                    'fecha'  => $aprobado ? '04/02/2026 10.15 am' : '',
                    'func'   => $aprobado ? 'CC 1234567890 - JUAN GOMEZ' : '-',
                ]);
            }
        }

        return $filas;
    }

    private function importar(array $filas, array $opts = [])
    {
        return $this->actingAs($this->user)
            ->post(route('aprendices.import'), ['archivo_excel' => $this->reporte($filas, $opts)]);
    }

    private function ultima(): Importacion
    {
        return Importacion::orderByDesc('id')->first();
    }

    private function cambios(string $tipo): \Illuminate\Support\Collection
    {
        return ImportacionCambio::where('importacion_id', $this->ultima()->id)->where('tipo', $tipo)->get();
    }

    public function test_la_primera_carga_es_inicial_y_no_registra_cambios(): void
    {
        $respuesta = $this->importar($this->filas([self::A . ':593147']));

        $respuesta->assertRedirect(route('importaciones.show', $this->ultima()));
        $this->assertStringContainsString('carga inicial', session('success'));

        $imp = $this->ultima();
        $this->assertSame(0, ImportacionCambio::count());
        $this->assertTrue($imp->resumen['carga_inicial']);
        $this->assertSame(2, $imp->resumen['aprendices']);
        $this->assertSame(4, $imp->resumen['juicios']);
        $this->assertSame(1, $imp->resumen['aprobados_en_formacion']);
        $this->assertSame(3, $imp->resumen['pendientes_en_formacion']);
        $this->assertSame($this->user->id, $imp->user_id, 'queda registrado quién subió el reporte');

        $this->actingAs($this->user)->get(route('importaciones.show', $imp))
            ->assertOk()->assertSee('Carga inicial de la ficha');
    }

    public function test_la_segunda_carga_registra_los_nuevos_aprobados(): void
    {
        $this->importar($this->filas());
        $this->importar($this->filas([self::A . ':593147', self::B . ':593148']));

        $aprobados = $this->cambios(ImportacionCambio::JUICIO_APROBADO);
        $this->assertCount(2, $aprobados);
        $this->assertSame(['POR EVALUAR'], $aprobados->pluck('valor_anterior')->unique()->values()->all());
        $this->assertSame(['APROBADO'], $aprobados->pluck('valor_nuevo')->unique()->values()->all());
        $this->assertSame(2, $this->ultima()->resumen['cambios'][ImportacionCambio::JUICIO_APROBADO]);
        $this->assertStringContainsString('+2 aprobados', session('success'));

        $this->actingAs($this->user)->get(route('importaciones.show', $this->ultima()))
            ->assertOk()->assertSee('Quién avanzó')->assertSee('APRENDIZ 1')->assertSee('593148');
    }

    public function test_registra_el_cambio_de_estado_y_la_foto_excluye_retirados(): void
    {
        $this->importar($this->filas());
        $this->importar($this->filas([], [self::B => 'RETIRO VOLUNTARIO']));

        $estado = $this->cambios(ImportacionCambio::APRENDIZ_ESTADO);
        $this->assertCount(1, $estado);
        $this->assertSame('EN FORMACION', $estado->first()->valor_anterior);
        $this->assertSame('RETIRO VOLUNTARIO', $estado->first()->valor_nuevo);

        // Los 2 juicios del retirado ya no cuentan como «por evaluar» de los que siguen en formación.
        $this->assertSame(2, $this->ultima()->resumen['pendientes_en_formacion']);
    }

    public function test_aprendices_nuevos_y_ausentes(): void
    {
        $this->importar($this->filas());
        $this->importar($this->filas([], [], [self::A, self::C]));

        $nuevos = $this->cambios(ImportacionCambio::APRENDIZ_NUEVO);
        $ausentes = $this->cambios(ImportacionCambio::APRENDIZ_AUSENTE);
        $this->assertSame([self::C], $nuevos->map(fn ($c) => $c->aprendiz->Documento)->all());
        $this->assertSame([self::B], $ausentes->map(fn ($c) => $c->aprendiz->Documento)->all());
        $this->assertSame('EN FORMACION', $ausentes->first()->valor_anterior);
        $this->assertCount(0, $this->cambios(ImportacionCambio::JUICIO_NUEVO), 'los RAP de un aprendiz nuevo no se anotan uno a uno');
        $this->assertNotNull(Aprendiz::where('Documento', self::B)->first(), 'un ausente no se borra');
    }

    public function test_un_reporte_mas_antiguo_revierte_y_avisa(): void
    {
        $this->importar($this->filas([self::A . ':593147']));
        $this->importar($this->filas([self::A . ':593147', self::A . ':593148']));
        $this->importar($this->filas([self::A . ':593147']));   // el reporte viejo otra vez

        $this->assertCount(1, $this->cambios(ImportacionCambio::JUICIO_REVERTIDO));
        $this->assertStringContainsString('más antiguo', session('warning'));

        $this->actingAs($this->user)->get(route('importaciones.show', $this->ultima()))
            ->assertOk()->assertSee('Aprobaciones revertidas');
    }

    public function test_el_mismo_reporte_dos_veces_no_tiene_cambios(): void
    {
        $filas = $this->filas([self::A . ':593147']);
        $this->importar($filas);
        $this->importar($filas);

        $this->assertSame(0, ImportacionCambio::where('importacion_id', $this->ultima()->id)->count());
        $this->assertFalse($this->ultima()->resumen['carga_inicial']);
        $this->assertStringContainsString('Sin cambios', session('success'));

        $this->actingAs($this->user)->get(route('importaciones.show', $this->ultima()))
            ->assertOk()->assertSee('Sin cambios frente a la carga anterior');
    }

    public function test_un_aprendiz_movido_de_ficha_se_registra_incluso_en_la_carga_inicial(): void
    {
        $this->importar($this->filas([], [], [self::A]), ['ficha' => '3142784']);
        $this->importar($this->filas([], [], [self::A]), ['ficha' => '2828282']);

        $movidos = $this->cambios(ImportacionCambio::APRENDIZ_MOVIDO);
        $this->assertCount(1, $movidos);
        $this->assertSame(['3142784', '2828282'], [$movidos->first()->valor_anterior, $movidos->first()->valor_nuevo]);
        $this->assertStringContainsString('otra ficha', session('warning'));
    }

    public function test_linea_de_tiempo_de_la_ficha_historial_y_expediente(): void
    {
        $this->importar($this->filas());
        $this->importar($this->filas([self::A . ':593147', self::B . ':593148']));

        $ficha = $this->actingAs($this->user)->get(route('fichas.historial', 3142784));
        $ficha->assertOk()->assertSee('Ver cambios');
        $this->assertSame([0, 2], $ficha->viewData('serie')['aprobados']);
        $this->assertSame([4, 2], $ficha->viewData('serie')['pendientes']);

        $this->actingAs($this->user)->get(route('importaciones.index'))
            ->assertOk()->assertSee('Carga inicial')->assertSee('+2 aprobados');

        $a = Aprendiz::where('Documento', self::A)->first();
        $this->actingAs($this->user)->get(route('aprendices.show', $a->Id_Aprendiz))
            ->assertOk()->assertSee('Historial de avance')->assertSee('+1 RAP aprobado(s)');
    }

    public function test_una_importacion_fallida_no_tiene_foto(): void
    {
        $this->importar($this->filas());
        // Ficha elegida distinta a la del archivo: se rechaza.
        $this->actingAs($this->user)->post(route('aprendices.import'), [
            'archivo_excel' => $this->reporte($this->filas(), ['ficha' => '3142784']),
            'Id_Ficha'      => $this->otraFicha(),
        ])->assertSessionHas('error');

        $fallida = $this->ultima();
        $this->assertNull($fallida->resumen);
        $this->actingAs($this->user)->get(route('importaciones.show', $fallida))
            ->assertOk()->assertSee('no registró datos');
        $this->assertSame(1, Importacion::whereNotNull('resumen')->count(), 'la fallida no entra en la línea de tiempo');
    }

    /** Solo PostgreSQL: una fila que falla (y su savepoint) no deja un cambio registrado. */
    public function test_una_fila_fallida_no_deja_cambios_en_postgresql(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Escenario específico de PostgreSQL.');
        }

        $this->importar($this->filas());

        $filas = $this->filas([self::A . ':593147', self::B . ':593147']);
        foreach ($filas as &$f) {
            if ($f['doc'] === self::A) {
                $f['nombre'] = str_repeat('X', 300);   // UPDATE que excede varchar(255): las filas de A fallan
            }
        }
        unset($f);
        $this->importar($filas)->assertSessionHas('warning_errores');

        $aprobados = $this->cambios(ImportacionCambio::JUICIO_APROBADO);
        $this->assertSame([self::B], $aprobados->map(fn ($c) => $c->aprendiz->Documento)->unique()->values()->all());
        $this->assertCount(0, $this->cambios(ImportacionCambio::APRENDIZ_AUSENTE), 'A vino en el archivo: no es ausente');
    }

    private function otraFicha(): int
    {
        $programa = \App\Models\Programa::first();
        \App\Models\Ficha::create(['Id_Ficha' => 2828282, 'Jornada' => 'DIURNA', 'Id_Programa' => $programa->Id_Programa]);

        return 2828282;
    }
}
