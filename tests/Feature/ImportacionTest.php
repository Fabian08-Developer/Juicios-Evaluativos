<?php

namespace Tests\Feature;

use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\JuicioEvaluativo;
use App\Models\Programa;
use App\Models\Resultado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaReporteSofia;
use Tests\TestCase;

class ImportacionTest extends TestCase
{
    use RefreshDatabase, CreaReporteSofia;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function importar(array $filas, array $opts = [], array $extra = [])
    {
        return $this->actingAs($this->user)
            ->post(route('aprendices.import'), ['archivo_excel' => $this->reporte($filas, $opts)] + $extra);
    }

    public static function variantesDeColumnas(): array
    {
        return ['compacto (10 columnas)' => [false], 'ancho (11 columnas)' => [true]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('variantesDeColumnas')]
    public function test_importa_las_dos_variantes_de_columnas_con_los_mismos_datos(bool $ancho): void
    {
        $this->importar([
            $this->fila(['doc' => '1000000001', 'juicio' => 'APROBADO', 'fecha' => '04/02/2026 10.15 am', 'func' => 'CC 1234567890 - JUAN GOMEZ']),
            $this->fila(['doc' => '1000000001', 'rap' => '593148 - 02 RESULTADO DOS', 'juicio' => 'POR EVALUAR']),
        ], ['ancho' => $ancho])->assertRedirect();

        $this->assertSame(1, Aprendiz::count());
        $this->assertSame(2, JuicioEvaluativo::count());

        // El funcionario es una persona, NO una fecha (el bug de la columna fija).
        $this->assertSame(1, Funcionario::count(), 'solo el funcionario real, ninguno inventado');
        $this->assertSame('JUAN GOMEZ', Funcionario::first()->Nombre);

        $aprobado  = JuicioEvaluativo::where('Estado', 1)->first();
        $pendiente = JuicioEvaluativo::where('Estado', 0)->first();
        $this->assertNotNull($aprobado->Id_Funcionario);
        $this->assertSame('2026-02-04 10:15', $aprobado->Hora->format('Y-m-d H:i'), 'fecha real del juicio, no la de importación');
        $this->assertNull($pendiente->Id_Funcionario, 'un juicio pendiente no tiene funcionario');
        $this->assertNull($pendiente->Fecha);
    }

    public function test_guarda_tipo_de_documento_programa_y_registro_de_importacion(): void
    {
        $this->importar([
            $this->fila(['doc' => '1000000001', 'tipo' => 'TI']),
            $this->fila(['doc' => '1000000002', 'tipo' => 'CC']),
            $this->fila(['doc' => '1000000002', 'rap' => '593148 - 02 DOS']),
        ]);

        $this->assertSame('TI', Aprendiz::where('Documento', '1000000001')->value('Tipo_Documento'));
        $this->assertSame('CC', Aprendiz::where('Documento', '1000000002')->value('Tipo_Documento'));

        $programa = Programa::first();
        $this->assertSame('ANALISIS Y DESARROLLO DE SOFTWARE.', $programa->Nombre);
        $this->assertSame('228118', $programa->Codigo);

        $imp = DB::table('importaciones')->first();
        $this->assertSame('3142784', $imp->id_ficha, 'la ficha detectada queda en el historial');
        $this->assertSame(2, (int) $imp->aprendices_procesados, 'aprendices únicos, no filas');
        $this->assertSame('exitoso', $imp->estado);
    }

    public function test_no_aprobado_no_cuenta_como_aprobado(): void
    {
        $this->importar([
            $this->fila(['doc' => '1000000001', 'juicio' => 'NO APROBADO']),
            $this->fila(['doc' => '1000000002', 'juicio' => 'APROBADO', 'fecha' => '04/02/2026 10.15 am', 'func' => 'CC 1234567890 - JUAN GOMEZ']),
        ]);

        $this->assertSame(0, (int) JuicioEvaluativo::whereHas('aprendiz', fn ($q) => $q->where('Documento', '1000000001'))->value('Estado'));
        $this->assertSame(1, (int) JuicioEvaluativo::whereHas('aprendiz', fn ($q) => $q->where('Documento', '1000000002'))->value('Estado'));
    }

    public function test_reimportar_el_mismo_archivo_no_duplica_nada(): void
    {
        $filas = [
            $this->fila(['doc' => '1000000001', 'juicio' => 'APROBADO', 'fecha' => '04/02/2026 10.15 am', 'func' => 'CC 1234567890 - JUAN GOMEZ']),
            $this->fila(['doc' => '1000000001', 'rap' => '593148 - 02 DOS']),
        ];

        $this->importar($filas);
        $this->importar($filas);

        $this->assertSame(1, Aprendiz::count());
        $this->assertSame(2, JuicioEvaluativo::count());
        $this->assertSame(1, Funcionario::count());
        $this->assertSame(2, DB::table('importaciones')->count());
    }

    public function test_rechaza_si_la_ficha_elegida_no_coincide_con_la_del_archivo(): void
    {
        $respuesta = $this->importar([$this->fila()], ['ficha' => '3142784'], ['Id_Ficha' => $this->crearFicha(2828282)]);

        $respuesta->assertSessionHas('error');
        $this->assertStringContainsString('3142784', session('error'));
        $this->assertStringContainsString('2828282', session('error'));
        $this->assertSame(0, Aprendiz::count(), 'no se importa nada en la ficha equivocada');
        $this->assertNull(Ficha::find(3142784));
        $this->assertSame('error', DB::table('importaciones')->value('estado'));
    }

    public function test_un_archivo_sin_aprendices_no_crea_ficha_ni_programa(): void
    {
        // La validación del archivo lo rechaza antes de crear nada.
        $this->importar([])->assertSessionHasErrors('archivo_excel');

        $this->assertSame(0, Ficha::count());
        $this->assertSame(0, Programa::count());
    }

    public function test_una_fila_defectuosa_se_omite_sin_perder_las_demas(): void
    {
        $respuesta = $this->importar([
            $this->fila(['doc' => '1000000001']),
            $this->fila(['doc' => '1000000002', 'comp' => '', 'rap' => '']),   // sin competencia ni resultado
            $this->fila(['doc' => '1000000003']),
        ]);

        $respuesta->assertSessionHas('warning')->assertSessionHas('warning_errores');
        $this->assertSame(2, Aprendiz::count());
        $this->assertCount(1, session('warning_errores'));
        $this->assertSame('con_advertencias', DB::table('importaciones')->value('estado'));
    }

    public function test_la_aprobacion_local_no_se_borra_pero_la_oficial_prevalece(): void
    {
        $this->importar([
            $this->fila(['doc' => '1000000001', 'rap' => '593147 - 01 UNO']),
            $this->fila(['doc' => '1000000001', 'rap' => '593148 - 02 DOS']),
        ]);

        $ap = Aprendiz::first();
        $r1 = Resultado::where('Codigo', '593147')->first();
        $r2 = Resultado::where('Codigo', '593148')->first();

        // El instructor aprueba ambos en la matriz.
        foreach ([$r1, $r2] as $r) {
            $this->actingAs($this->user)->postJson(route('acciones.matriz.actualizar'), [
                'id_aprendiz' => $ap->Id_Aprendiz, 'id_resultado' => $r->Id_Resultado, 'estado' => 1,
            ])->assertOk();
        }
        $this->assertSame($this->user->id, JuicioEvaluativo::first()->registrado_por);

        // Sofia Plus aún dice POR EVALUAR en el 1.º y ya trae APROBADO en el 2.º.
        $respuesta = $this->importar([
            $this->fila(['doc' => '1000000001', 'rap' => '593147 - 01 UNO', 'juicio' => 'POR EVALUAR']),
            $this->fila(['doc' => '1000000001', 'rap' => '593148 - 02 DOS', 'juicio' => 'APROBADO', 'fecha' => '05/02/2026 8.00 am', 'func' => 'CC 1234567890 - JUAN GOMEZ']),
        ]);

        $respuesta->assertSessionHas('warning');
        $this->assertStringContainsString('aprobados manualmente', session('warning'));

        $j1 = JuicioEvaluativo::where('Id_Resultado', $r1->Id_Resultado)->first();
        $j2 = JuicioEvaluativo::where('Id_Resultado', $r2->Id_Resultado)->first();
        $this->assertSame(1, (int) $j1->Estado, 'la aprobación local se conserva');
        $this->assertNotNull($j1->registrado_por);
        $this->assertSame(1, (int) $j2->Estado);
        $this->assertNull($j2->registrado_por, 'el dato oficial reemplaza al local');
        $this->assertNotNull($j2->Id_Funcionario);
    }

    public function test_un_juicio_aprobado_oficialmente_si_vuelve_a_pendiente_cuando_el_excel_lo_dice(): void
    {
        $aprobado = $this->fila(['juicio' => 'APROBADO', 'fecha' => '04/02/2026 10.15 am', 'func' => 'CC 1234567890 - JUAN GOMEZ']);
        $this->importar([$aprobado]);
        $this->importar([$this->fila(['juicio' => 'POR EVALUAR'])]);

        $this->assertSame(0, (int) JuicioEvaluativo::first()->Estado, 'no era una edición local: el Excel manda');
    }

    public function test_un_aprendiz_que_aparece_en_otra_ficha_se_mueve_y_se_avisa(): void
    {
        $this->importar([$this->fila(['doc' => '1000000001'])], ['ficha' => '3142784']);
        $respuesta = $this->importar([$this->fila(['doc' => '1000000001'])], ['ficha' => '2828282']);

        $respuesta->assertSessionHas('warning');
        $this->assertStringContainsString('otra ficha', session('warning'));
        $this->assertSame(2828282, (int) Aprendiz::first()->Id_Ficha);
        $this->assertSame(1, JuicioEvaluativo::count(), 'el historial de juicios se conserva');
    }

    public function test_el_tipo_de_documento_y_el_estado_se_actualizan_al_reimportar(): void
    {
        $this->importar([$this->fila(['tipo' => 'TI', 'estado' => 'EN FORMACION'])]);
        $this->importar([$this->fila(['tipo' => 'CC', 'estado' => 'RETIRO VOLUNTARIO'])]);

        $this->assertSame('CC', Aprendiz::first()->Tipo_Documento);
        $this->assertSame('RETIRO VOLUNTARIO', Aprendiz::first()->Estado);
    }

    public function test_rechaza_un_excel_que_no_es_el_reporte_de_sofia(): void
    {
        $libro = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $libro->getActiveSheet()->fromArray([['Producto', 'Ventas'], ['A', 10], ['B', 20]], null, 'A1');
        $ruta = tempnam(sys_get_temp_dir(), 'v_') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro))->save($ruta);
        $archivo = new \Illuminate\Http\UploadedFile($ruta, 'ventas.xlsx', null, null, true);

        $this->actingAs($this->user)->post(route('aprendices.import'), ['archivo_excel' => $archivo])
            ->assertSessionHasErrors('archivo_excel');
        $this->assertSame(0, DB::table('importaciones')->count());
    }

    /**
     * Solo PostgreSQL: allí un error de SQL aborta la transacción y antes dejaba
     * sin guardar TODAS las filas posteriores mientras la pantalla decía que sí.
     */
    public function test_un_error_de_sql_en_una_fila_no_pierde_las_demas_en_postgresql(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Escenario específico de PostgreSQL.');
        }

        $this->importar([$this->fila(['doc' => '2000000001', 'nombre' => 'UNO'])]);

        $respuesta = $this->importar([
            $this->fila(['doc' => '2000000003', 'nombre' => 'TRES']),
            $this->fila(['doc' => '2000000001', 'nombre' => str_repeat('X', 300)]),   // UPDATE que excede varchar(255)
            $this->fila(['doc' => '2000000004', 'nombre' => 'CUATRO']),
            $this->fila(['doc' => '2000000005', 'nombre' => 'CINCO']),
        ]);

        $respuesta->assertSessionHas('warning_errores');
        $this->assertCount(1, session('warning_errores'), 'solo la fila mala falla');
        $this->assertEqualsCanonicalizing(
            ['2000000001', '2000000003', '2000000004', '2000000005'],
            Aprendiz::pluck('Documento')->all(),
            'las filas anteriores y posteriores sí se guardaron'
        );
    }

    private function crearFicha(int $id): int
    {
        $programa = Programa::firstOrCreate(['Nombre' => 'OTRO'], ['Codigo' => 'X', 'Modalidad' => 'P', 'Version' => '1']);
        Ficha::create(['Id_Ficha' => $id, 'Jornada' => 'DIURNA', 'Id_Programa' => $programa->Id_Programa]);

        return $id;
    }
}
