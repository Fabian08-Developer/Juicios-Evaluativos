<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Interpreta el "Reporte de Juicios de Evaluación" exportado de Sofia Plus.
 *
 * Estructura real del archivo (verificada con exportaciones reales):
 *
 *   Filas 1-12  Metadatos: "Ficha de Caracterización:", "Cógigo:" (sic),
 *               "Versión:", "Denominación:", "Modalidad de Formación:", ...
 *               La etiqueta va en la columna A y el valor en la C.
 *   Fila 13     Encabezados de columnas.
 *   Fila 14+    Un registro por (aprendiz, resultado de aprendizaje).
 *
 * IMPORTANTE: las columnas NO están en una posición fija. Algunas
 * exportaciones traen 10 columnas (la fecha en la I y el funcionario en la J)
 * y otras 11+ (con una columna vacía: fecha en la J y funcionario en la K).
 * Por eso se ubican SIEMPRE por el texto del encabezado, nunca por índice.
 *
 * Esta clase es pura (no toca la base de datos): recibe las filas y devuelve
 * registros normalizados más la lista de filas con problemas.
 */
final class ReporteSofiaPlus
{
    /** Columnas sin las que no se puede importar. */
    public const COLUMNAS_REQUERIDAS = [
        'documento'   => 'Número de Documento',
        'nombre'      => 'Nombre',
        'competencia' => 'Competencia',
        'resultado'   => 'Resultado de Aprendizaje',
        'juicio'      => 'Juicio de Evaluación',
    ];

    /** Valores de "Juicio de Evaluación" que Sofia Plus puede producir. */
    private const JUICIOS_CONOCIDOS = ['APROBADO', 'NO APROBADO', 'POR EVALUAR'];

    /** Filas que se examinan buscando metadatos y encabezado. */
    public const FILAS_CABECERA = 40;

    /** Mayor valor que cabe en aprendiz.Id_Ficha (columna integer). */
    private const MAX_FICHA = 2147483647;

    public ?string $ficha = null;
    public ?string $denominacion = null;
    public ?string $codigoPrograma = null;
    public ?string $versionPrograma = null;
    public ?string $modalidad = null;

    /** @var array<string,int> clave de columna => índice */
    public array $columnas = [];
    public int $filaEncabezado = -1;

    /** @var array<int,array<string,mixed>> */
    public array $registros = [];

    /** @var array<int,array{fila:int,dato:string,error:string}> */
    public array $errores = [];

    /** @var array<string,int> juicios con texto no reconocido => cantidad */
    public array $juiciosDesconocidos = [];

    /** Cantidad de registros con funcionario presente pero ilegible. */
    public int $funcionariosIlegibles = 0;

    private function __construct() {}

    // ══════════════════════════════════════════════════════════════════════
    //  API pública
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Analiza un reporte completo.
     *
     * @param  array<int,array<int,mixed>>  $filas
     * @throws \RuntimeException si no se reconoce la estructura del archivo
     */
    public static function desdeFilas(array $filas): self
    {
        $r = new self();
        $faltantes = $r->analizarCabecera($filas);

        if ($r->filaEncabezado < 0) {
            throw new \RuntimeException(
                'No se encontró la fila de encabezados del reporte (Número de Documento, Nombre, Competencia, ...). ' .
                'Verifica que sea el "Reporte de Juicios de Evaluación" descargado de Sofia Plus y que no haya sido modificado.'
            );
        }
        if ($faltantes) {
            throw new \RuntimeException(
                'Al reporte le faltan columnas obligatorias: ' . implode(', ', $faltantes) . '. ' .
                'Verifica que sea el "Reporte de Juicios de Evaluación" completo de Sofia Plus.'
            );
        }

        $r->leerRegistros($filas);

        return $r;
    }

    /**
     * Revisa solo la cabecera (para validar un archivo sin procesarlo).
     *
     * @param  array<int,array<int,mixed>>  $filas
     * @return array<int,string>  Lista de problemas; vacía si la cabecera es válida.
     */
    public static function problemasDeCabecera(array $filas): array
    {
        $r = new self();
        $faltantes = $r->analizarCabecera($filas);

        if ($r->filaEncabezado < 0) {
            return ['no se encontró la fila de encabezados (Número de Documento, Nombre, Competencia, ...)'];
        }

        return array_map(fn ($c) => "columna «{$c}»", $faltantes);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Cabecera y metadatos
    // ══════════════════════════════════════════════════════════════════════

    /** @return array<int,string> nombres legibles de las columnas requeridas que faltan */
    private function analizarCabecera(array $filas): array
    {
        $limite = min(count($filas), self::FILAS_CABECERA);

        for ($i = 0; $i < $limite; $i++) {
            $mapa = $this->mapearColumnas($filas[$i]);
            if (isset($mapa['documento'], $mapa['nombre'])) {
                $this->filaEncabezado = $i;
                $this->columnas = $mapa;
                break;
            }
        }

        $hasta = $this->filaEncabezado >= 0 ? $this->filaEncabezado : $limite;
        for ($i = 0; $i < $hasta; $i++) {
            $this->leerMetadato($filas[$i]);
        }

        if ($this->filaEncabezado < 0) {
            return [];
        }

        $faltantes = [];
        foreach (self::COLUMNAS_REQUERIDAS as $clave => $nombre) {
            if (! isset($this->columnas[$clave])) {
                $faltantes[] = $nombre;
            }
        }

        return $faltantes;
    }

    /** @return array<string,int> */
    private function mapearColumnas(array $fila): array
    {
        $mapa = [];

        foreach ($fila as $idx => $celda) {
            $e = self::normalizar($celda);
            if ($e === '') {
                continue;
            }

            $clave = match (true) {
                str_starts_with($e, 'TIPO DE DOC'), str_starts_with($e, 'TIPO DOC') => 'tipo_documento',
                str_starts_with($e, 'NUMERO DE DOC'), str_starts_with($e, 'NUMERO DOC'), $e === 'DOCUMENTO' => 'documento',
                $e === 'NOMBRE', $e === 'NOMBRES' => 'nombre',
                str_starts_with($e, 'APELLIDO') => 'apellidos',
                $e === 'ESTADO' => 'estado',
                str_starts_with($e, 'COMPETENCIA') => 'competencia',
                str_starts_with($e, 'RESULTADO') => 'resultado',
                str_starts_with($e, 'JUICIO') => 'juicio',
                str_starts_with($e, 'FECHA') => 'fecha',
                str_starts_with($e, 'FUNCIONARIO') => 'funcionario',
                default => null,
            };

            if ($clave !== null && ! isset($mapa[$clave])) {
                $mapa[$clave] = (int) $idx;
            }
        }

        return $mapa;
    }

    private function leerMetadato(array $fila): void
    {
        $celdas = [];
        foreach ($fila as $celda) {
            $t = trim((string) $celda);
            if ($t !== '') {
                $celdas[] = $t;
            }
        }
        if (count($celdas) < 2) {
            return;
        }

        $etiqueta = rtrim(self::normalizar($celdas[0]), ': ');
        $valor    = $celdas[1];

        switch ($etiqueta) {
            case 'FICHA DE CARACTERIZACION':
                if (preg_match('/\d{4,}/', $valor, $m)) {
                    $this->ficha ??= $m[0];
                }
                break;
            case 'DENOMINACION':
                // Se conserva el texto tal cual (incluido el punto final que
                // trae Sofia Plus) para coincidir con programas ya registrados.
                $this->denominacion ??= $valor;
                break;
            case 'COGIGO': // Sofia Plus escribe "Cógigo" (sic)
            case 'CODIGO':
                $this->codigoPrograma ??= $valor;
                break;
            case 'VERSION':
                $this->versionPrograma ??= $valor;
                break;
            case 'MODALIDAD DE FORMACION':
                $this->modalidad ??= $valor;
                break;
        }

        // Respaldo para variantes de formato: cualquier fila "Ficha ... N".
        if ($this->ficha === null && str_contains($etiqueta, 'FICHA') && ! str_contains($etiqueta, 'ESTADO')
            && preg_match('/\d{6,}/', implode(' ', $celdas), $m)) {
            $this->ficha = $m[0];
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Registros
    // ══════════════════════════════════════════════════════════════════════

    private function leerRegistros(array $filas): void
    {
        if ($this->ficha !== null && (int) $this->ficha > self::MAX_FICHA) {
            throw new \RuntimeException("El número de ficha detectado ({$this->ficha}) no es válido.");
        }

        for ($i = $this->filaEncabezado + 1; $i < count($filas); $i++) {
            $fila = $filas[$i];
            $numeroFila = $i + 1;

            if ($this->filaVacia($fila)) {
                continue;
            }

            $documento = self::limpiarDocumento($this->celda($fila, 'documento'));
            if ($documento === '') {
                $this->errores[] = ['fila' => $numeroFila, 'dato' => 'N/A', 'error' => 'La fila no tiene número de documento.'];
                continue;
            }

            [$codComp, $nomComp] = self::separarCodigoNombre($this->celda($fila, 'competencia'));
            [$codRes, $nomRes]   = self::separarCodigoNombre($this->celda($fila, 'resultado'));

            if ($codComp === '' || $codRes === '') {
                $this->errores[] = [
                    'fila'  => $numeroFila,
                    'dato'  => $documento,
                    'error' => 'La fila no tiene competencia o resultado de aprendizaje.',
                ];
                continue;
            }

            if (mb_strlen($codComp) > 100 || mb_strlen($codRes) > 100) {
                $this->errores[] = ['fila' => $numeroFila, 'dato' => $documento, 'error' => 'Código de competencia o resultado demasiado largo.'];
                continue;
            }

            $juicioTexto = self::normalizar($this->celda($fila, 'juicio'));
            if (! in_array($juicioTexto, self::JUICIOS_CONOCIDOS, true)) {
                $clave = $juicioTexto === '' ? '(vacío)' : $juicioTexto;
                $this->juiciosDesconocidos[$clave] = ($this->juiciosDesconocidos[$clave] ?? 0) + 1;
            }

            $textoFuncionario = trim((string) $this->celda($fila, 'funcionario'));
            $funcionario = self::parsearFuncionario($textoFuncionario);
            if ($funcionario === null && ! in_array($textoFuncionario, ['', '-'], true)) {
                $this->funcionariosIlegibles++;
            }

            $this->registros[] = [
                'fila'             => $numeroFila,
                'tipo_documento'   => self::limpiarTipoDocumento($this->celda($fila, 'tipo_documento')),
                'documento'        => $documento,
                'nombre'           => self::limpiarTexto($this->celda($fila, 'nombre')) ?: 'N/A',
                'apellidos'        => self::limpiarTexto($this->celda($fila, 'apellidos')) ?: 'N/A',
                'estado'           => self::normalizarEstado($this->celda($fila, 'estado')),
                'competencia_cod'  => $codComp,
                'competencia_nom'  => $nomComp,
                'resultado_cod'    => $codRes,
                'resultado_nom'    => $nomRes,
                // Solo "APROBADO" exacto aprueba: "NO APROBADO" contiene la
                // palabra "APROB" y NO debe contarse como aprobado.
                'aprobado'         => $juicioTexto === 'APROBADO',
                'fecha'            => self::parsearFechaHora($this->celda($fila, 'fecha')),
                'funcionario'      => $funcionario,
            ];
        }
    }

    private function celda(array $fila, string $clave): mixed
    {
        $idx = $this->columnas[$clave] ?? null;

        return $idx === null ? null : ($fila[$idx] ?? null);
    }

    private function filaVacia(array $fila): bool
    {
        foreach ($fila as $celda) {
            if (trim((string) $celda) !== '') {
                return false;
            }
        }

        return true;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Normalización (públicas para poder probarlas de forma aislada)
    // ══════════════════════════════════════════════════════════════════════

    /** Mayúsculas, sin tildes, espacios colapsados. "Número de Documento" => "NUMERO DE DOCUMENTO". */
    public static function normalizar(mixed $valor): string
    {
        $t = Str::ascii(trim((string) $valor));

        return mb_strtoupper(preg_replace('/\s+/', ' ', $t) ?? '');
    }

    public static function limpiarTexto(mixed $valor): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $valor) ?? '');
    }

    public static function limpiarDocumento(mixed $valor): string
    {
        // Celdas numéricas de .xls llegan como float: sin notación científica.
        if (is_float($valor) || is_int($valor)) {
            return sprintf('%.0f', $valor);
        }

        return preg_replace('/[\s.,]/', '', trim((string) $valor)) ?? '';
    }

    public static function limpiarTipoDocumento(mixed $valor): string
    {
        $t = mb_substr(self::normalizar($valor), 0, 10);

        return $t === '' ? 'CC' : $t;
    }

    public static function normalizarEstado(mixed $valor): string
    {
        $t = mb_strtoupper(self::limpiarTexto($valor));

        return $t === '' ? 'EN FORMACION' : $t;
    }

    /**
     * "593147 - 02  ESTABLECER ..." => ['593147', '02 ESTABLECER ...'].
     * Sin separador, el texto completo sirve de código y de nombre.
     *
     * @return array{0:string,1:string}
     */
    public static function separarCodigoNombre(mixed $valor): array
    {
        $t = self::limpiarTexto($valor);
        if ($t === '') {
            return ['', ''];
        }

        $partes = preg_split('/\s+-\s+/u', $t, 2);

        return count($partes) === 2 ? [trim($partes[0]), trim($partes[1])] : [$t, $t];
    }

    /**
     * "CC 1006419673 - NOMBRE APELLIDO" => ['tipo' => 'CC', 'documento' => '1006419673', 'nombre' => '...'].
     * "-" o vacío (juicio todavía no registrado) => null.
     *
     * @return array{tipo:string,documento:string,nombre:string}|null
     */
    public static function parsearFuncionario(string $texto): ?array
    {
        $texto = trim($texto);
        if ($texto === '' || $texto === '-') {
            return null;
        }

        if (preg_match('/^\s*([A-Za-z]{1,6})?\s*(\d{4,20})\s+-\s+(.+)$/u', $texto, $m)) {
            return [
                'tipo'      => $m[1] !== '' ? mb_strtoupper($m[1]) : 'CC',
                'documento' => $m[2],
                'nombre'    => self::limpiarTexto($m[3]),
            ];
        }

        return null;
    }

    /**
     * "04/02/2026 10.15 am", "30/09/2026 9.05 pm", "04/02/2026" o un número
     * de serie de Excel => fecha y hora. Vacío o "-" => null.
     */
    public static function parsearFechaHora(mixed $valor): ?CarbonImmutable
    {
        if ($valor === null) {
            return null;
        }

        $tz = config('app.timezone');

        if (is_int($valor) || is_float($valor)) {
            return $valor > 20000
                ? CarbonImmutable::instance(ExcelDate::excelToDateTimeObject($valor))->shiftTimezone($tz)
                : null;
        }

        $t = mb_strtolower(trim((string) $valor));
        if ($t === '' || $t === '-') {
            return null;
        }

        // dd/mm/aaaa  [h.mm am|pm]   (también acepta "h:mm", "a. m." y hora de 24 h)
        if (! preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})(?:\s+(\d{1,2})[.:](\d{2})\s*(?:([ap])\.?\s*m\.?)?)?$#u', $t, $m)) {
            return null;
        }

        [$dia, $mes, $anio] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        if (! checkdate($mes, $dia, $anio)) {
            return null;
        }

        $hora = isset($m[4]) ? (int) $m[4] : 0;
        $min  = isset($m[5]) ? (int) $m[5] : 0;
        $ampm = $m[6] ?? '';

        if ($ampm === 'p' && $hora < 12) {
            $hora += 12;
        } elseif ($ampm === 'a' && $hora === 12) {
            $hora = 0;
        }
        if ($hora > 23 || $min > 59) {
            return null;
        }

        return CarbonImmutable::create($anio, $mes, $dia, $hora, $min, 0, $tz);
    }
}
