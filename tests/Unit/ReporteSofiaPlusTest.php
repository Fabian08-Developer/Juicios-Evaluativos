<?php

namespace Tests\Unit;

use App\Support\ReporteSofiaPlus;
use Tests\TestCase;

class ReporteSofiaPlusTest extends TestCase
{
    private function filas(array $encabezado, array $datos, string $ficha = '3142784'): array
    {
        return array_merge([
            ['Reporte de Juicios de Evaluación'],
            ['Ficha de Caracterización:', '', $ficha],
            ['Cógigo:', '', '228118'],
            ['Versión:', '', '01'],
            ['Denominación:', '', 'ANALISIS Y DESARROLLO DE SOFTWARE.'],
            ['Estado de la Ficha de Caracterización:', '', 'EN EJECUCION'],
            ['Modalidad de Formación:', '', 'PRESENCIAL'],
            $encabezado,
        ], $datos);
    }

    private const ENCABEZADO_COMPACTO = ['Tipo de Documento', 'Número de Documento', 'Nombre', 'Apellidos', 'Estado', 'Competencia',
        'Resultado de Aprendizaje', 'Juicio de Evaluación', 'Fecha y Hora del Juicio Evaluativo', 'Funcionario que registro el juicio evaluativo'];

    private const ENCABEZADO_ANCHO = ['Tipo de Documento', 'Número de Documento', 'Nombre', 'Apellidos', 'Estado', 'Competencia',
        'Resultado de Aprendizaje', 'Juicio de Evaluación', '', 'Fecha y Hora del Juicio Evaluativo', 'Funcionario que registro el juicio evaluativo'];

    public function test_ubica_las_columnas_por_encabezado_en_las_dos_variantes(): void
    {
        $compacto = ReporteSofiaPlus::desdeFilas($this->filas(self::ENCABEZADO_COMPACTO, [
            ['CC', '1006419673', 'ANA', 'PEREZ', 'EN FORMACION', '2 - COMP', '1 - RAP', 'APROBADO', '04/02/2026 10.15 am', 'CC 1234567890 - JUAN GOMEZ'],
        ]));
        $ancho = ReporteSofiaPlus::desdeFilas($this->filas(self::ENCABEZADO_ANCHO, [
            ['CC', '1006419673', 'ANA', 'PEREZ', 'EN FORMACION', '2 - COMP', '1 - RAP', 'APROBADO', '', '04/02/2026 10.15 am', 'CC 1234567890 - JUAN GOMEZ'],
        ]));

        $this->assertSame(8, $compacto->columnas['fecha']);
        $this->assertSame(9, $compacto->columnas['funcionario']);
        $this->assertSame(9, $ancho->columnas['fecha']);
        $this->assertSame(10, $ancho->columnas['funcionario']);

        foreach ([$compacto, $ancho] as $r) {
            $this->assertSame('3142784', $r->ficha);
            $this->assertCount(1, $r->registros);
            $reg = $r->registros[0];
            $this->assertSame('2026-02-04 10:15', $reg['fecha']->format('Y-m-d H:i'));
            $this->assertSame(['tipo' => 'CC', 'documento' => '1234567890', 'nombre' => 'JUAN GOMEZ'], $reg['funcionario']);
        }
    }

    public function test_lee_metadatos_del_programa(): void
    {
        $r = ReporteSofiaPlus::desdeFilas($this->filas(self::ENCABEZADO_COMPACTO, [
            ['CC', '1006419673', 'ANA', 'PEREZ', 'EN FORMACION', '2 - COMP', '1 - RAP', 'APROBADO', '', '-'],
        ]));

        // El punto final se conserva a propósito para coincidir con programas ya registrados.
        $this->assertSame('ANALISIS Y DESARROLLO DE SOFTWARE.', $r->denominacion);
        $this->assertSame('228118', $r->codigoPrograma);
        $this->assertSame('PRESENCIAL', $r->modalidad);
    }

    public function test_solo_aprobado_exacto_aprueba(): void
    {
        $datos = [];
        foreach (['APROBADO', 'NO APROBADO', 'POR EVALUAR', 'Aprobado ', 'EXTRAÑO', ''] as $i => $juicio) {
            $datos[] = ['CC', '10000000' . $i, 'A', 'B', 'EN FORMACION', '2 - C', '1 - R', $juicio, '', '-'];
        }

        $r = ReporteSofiaPlus::desdeFilas($this->filas(self::ENCABEZADO_COMPACTO, $datos));
        $aprobado = array_column($r->registros, 'aprobado');

        // APROBADO, NO APROBADO, POR EVALUAR, "Aprobado " (normaliza), EXTRAÑO, vacío
        $this->assertSame([true, false, false, true, false, false], $aprobado);
        $this->assertSame(['EXTRANO' => 1, '(vacío)' => 1], $r->juiciosDesconocidos); // el texto se normaliza (sin tildes)
    }

    public function test_conserva_el_tipo_de_documento_y_el_estado_del_aprendiz(): void
    {
        $r = ReporteSofiaPlus::desdeFilas($this->filas(self::ENCABEZADO_COMPACTO, [
            ['TI', '1006419673', 'ANA', 'PEREZ', 'RETIRO VOLUNTARIO', '2 - C', '1 - R', 'POR EVALUAR', '', '-'],
        ]));

        $this->assertSame('TI', $r->registros[0]['tipo_documento']);
        $this->assertSame('RETIRO VOLUNTARIO', $r->registros[0]['estado']);
    }

    public function test_funcionario_guion_es_nulo_y_no_inventa_registro(): void
    {
        $this->assertNull(ReporteSofiaPlus::parsearFuncionario('-'));
        $this->assertNull(ReporteSofiaPlus::parsearFuncionario(''));
        $this->assertNull(ReporteSofiaPlus::parsearFuncionario('04/02/2026 10.15 am'));
        $this->assertSame(
            ['tipo' => 'CE', 'documento' => '987654321', 'nombre' => 'MARIA DEL CARMEN RUIZ'],
            ReporteSofiaPlus::parsearFuncionario('CE 987654321 - MARIA  DEL CARMEN RUIZ')
        );
    }

    public function test_fechas(): void
    {
        $f = fn ($v) => ReporteSofiaPlus::parsearFechaHora($v)?->format('Y-m-d H:i');

        $this->assertSame('2026-02-04 10:15', $f('04/02/2026 10.15 am'));
        $this->assertSame('2026-09-30 21:05', $f('30/09/2026 9.05 pm'));
        $this->assertSame('2026-09-30 00:00', $f('30/09/2026 12.00 am'));
        $this->assertSame('2026-09-30 12:30', $f('30/09/2026 12.30 pm'));
        $this->assertSame('2026-02-04 00:00', $f('04/02/2026'));
        $this->assertNull($f('31/02/2026 10.15 am'), 'fecha inexistente');
        $this->assertNull($f('-'));
        $this->assertNull($f(''));
        $this->assertNull($f(null));
        $this->assertNull($f('no es una fecha'));
    }

    public function test_separa_codigo_y_nombre(): void
    {
        $this->assertSame(['593147', '02 ESTABLECER RELACIONES'], ReporteSofiaPlus::separarCodigoNombre('593147 - 02  ESTABLECER RELACIONES'));
        $this->assertSame(['36180', 'Enrique Low Murtra-Interactuar'], ReporteSofiaPlus::separarCodigoNombre('36180 - Enrique Low Murtra-Interactuar'));
        $this->assertSame(['SIN SEPARADOR', 'SIN SEPARADOR'], ReporteSofiaPlus::separarCodigoNombre('SIN SEPARADOR'));
        $this->assertSame(['', ''], ReporteSofiaPlus::separarCodigoNombre(''));
    }

    public function test_exige_las_columnas_obligatorias(): void
    {
        $sinJuicio = array_values(array_filter(self::ENCABEZADO_COMPACTO, fn ($c) => $c !== 'Juicio de Evaluación'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Juicio de Evaluación');
        ReporteSofiaPlus::desdeFilas($this->filas($sinJuicio, []));
    }

    public function test_rechaza_un_archivo_sin_encabezados(): void
    {
        $this->expectException(\RuntimeException::class);
        ReporteSofiaPlus::desdeFilas([['Ventas'], ['Enero', 100], ['Febrero', 200]]);
    }

    public function test_filas_incompletas_se_reportan_y_no_detienen_el_resto(): void
    {
        $r = ReporteSofiaPlus::desdeFilas($this->filas(self::ENCABEZADO_COMPACTO, [
            ['CC', '', 'SIN', 'DOC', 'EN FORMACION', '2 - C', '1 - R', 'APROBADO', '', '-'],
            ['CC', '1006419673', 'SIN', 'RAP', 'EN FORMACION', '', '', 'APROBADO', '', '-'],
            ['CC', '1006419674', 'OK', 'OK', 'EN FORMACION', '2 - C', '1 - R', 'APROBADO', '', '-'],
            [], // fila vacía: se ignora sin error
        ]));

        $this->assertCount(1, $r->registros);
        $this->assertCount(2, $r->errores);
        $this->assertSame([9, 10], array_column($r->errores, 'fila'));
    }

    public function test_rechaza_una_ficha_que_no_cabe_en_la_base_de_datos(): void
    {
        $this->expectException(\RuntimeException::class);
        ReporteSofiaPlus::desdeFilas($this->filas(self::ENCABEZADO_COMPACTO, [
            ['CC', '1006419673', 'A', 'B', 'EN FORMACION', '2 - C', '1 - R', 'APROBADO', '', '-'],
        ], '99999999999'));
    }
}
