<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

/**
 * Genera reportes .xls con la misma estructura que el "Reporte de Juicios de
 * Evaluación" real de Sofia Plus (sin datos personales reales):
 *   filas 1-12 metadatos (etiqueta en A, valor en C), fila 13 encabezados, 14+ datos.
 *
 * Existen dos variantes de columnas en las exportaciones reales:
 *   compacto: fecha en la columna 8 y funcionario en la 9 (10 columnas)
 *   ancho:    columna 8 vacía, fecha en la 9 y funcionario en la 10 (11 columnas)
 */
trait CreaReporteSofia
{
    /** Fila de datos con valores por omisión; sobrescribe lo que necesites. */
    protected function fila(array $o = []): array
    {
        return array_merge([
            'tipo'      => 'CC',
            'doc'       => '1000000001',
            'nombre'    => 'ANA MARIA',
            'apellidos' => 'PEREZ LOPEZ',
            'estado'    => 'EN FORMACION',
            'comp'      => '220501001 - Competencia A',
            'rap'       => '593147 - 01 RESULTADO UNO',
            'juicio'    => 'POR EVALUAR',
            'fecha'     => '',
            'func'      => '-',
        ], $o);
    }

    /** @param  array<int,array<string,string>>  $filas */
    protected function reporte(array $filas, array $opts = []): UploadedFile
    {
        $ficha = $opts['ficha'] ?? '3142784';
        $ancho = $opts['ancho'] ?? false;

        $meta = [
            ['Reporte de Juicios de Evaluación'],
            ['Fecha del Reporte:', '', '30/09/2026'],
            ['Ficha de Caracterización:', '', $ficha],
            ['Cógigo:', '', '228118'],
            ['Versión:', '', '01'],
            ['Denominación:', '', $opts['programa'] ?? 'ANALISIS Y DESARROLLO DE SOFTWARE.'],
            ['Estado de la Ficha de Caracterización:', '', 'EN EJECUCION'],
            ['Fecha Inicio:', '', '10/02/2025'],
            ['Fecha Fin:', '', '10/05/2027'],
            ['Modalidad de Formación:', '', 'PRESENCIAL'],
            ['Regional:', '', '18 - REGIONAL CAQUETÁ'],
            ['Centro de Formación:', '', '9516 - CENTRO TECNOLOGICO DE LA AMAZONIA'],
        ];

        $encabezado = ['Tipo de Documento', 'Número de Documento', 'Nombre', 'Apellidos', 'Estado', 'Competencia',
            'Resultado de Aprendizaje', 'Juicio de Evaluación'];
        $encabezado = $ancho
            ? array_merge($encabezado, ['', 'Fecha y Hora del Juicio Evaluativo', 'Funcionario que registro el juicio evaluativo'])
            : array_merge($encabezado, ['Fecha y Hora del Juicio Evaluativo', 'Funcionario que registro el juicio evaluativo']);

        $filasExcel = array_merge($meta, [$encabezado]);
        foreach ($filas as $f) {
            $base = [$f['tipo'], $f['doc'], $f['nombre'], $f['apellidos'], $f['estado'], $f['comp'], $f['rap'], $f['juicio']];
            $filasExcel[] = $ancho
                ? array_merge($base, ['', $f['fecha'], $f['func']])
                : array_merge($base, [$f['fecha'], $f['func']]);
        }

        $libro = new Spreadsheet();
        $hoja  = $libro->getActiveSheet();
        foreach ($filasExcel as $i => $celdas) {
            foreach ($celdas as $j => $valor) {
                if ($valor !== '') {
                    $hoja->setCellValueExplicit([$j + 1, $i + 1], (string) $valor, DataType::TYPE_STRING);
                }
            }
        }

        $ruta = tempnam(sys_get_temp_dir(), 'sofia_') . '.xls';
        (new Xls($libro))->save($ruta);

        return new UploadedFile($ruta, 'Reporte_de_Juicios_Evaluativos.xls', 'application/vnd.ms-excel', null, true);
    }

    /**
     * Responde la pantalla «Revisa antes de aplicar» a la que redirigió la carga.
     * $accion: preservar | trasladar | forzar | cancelar
     */
    protected function decidir(\Illuminate\Testing\TestResponse $carga, string $accion): \Illuminate\Testing\TestResponse
    {
        $url = (string) $carga->headers->get('Location');
        $this->assertMatchesRegularExpression('#/aprendices/importar/[0-9a-f-]{36}$#', $url, 'la carga debía pedir una decisión');

        return $this->post($url, ['accion' => $accion]);
    }
}
