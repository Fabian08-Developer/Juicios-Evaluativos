<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * MEJORA TÉCNICA #3 — Eloquent Scopes
 *
 * Antes: `where('Estado', 'EN FORMACION')` repetido en 3 controladores.
 * Ahora: `Aprendiz::enFormacion()` — definido en un solo lugar (DRY Principle).
 *
 * Si el valor del campo cambia, se actualiza aquí y se propaga a todo el sistema.
 */
class Aprendiz extends Model
{
    use HasFactory;

    protected $table      = 'aprendiz';
    protected $primaryKey = 'Id_Aprendiz';
    protected $fillable   = ['Tipo_Documento', 'Documento', 'Nombre', 'Apellido', 'Estado', 'Id_Ficha'];

    // ═══════════════════════════════════════════════════════════
    //  RELACIONES
    // ═══════════════════════════════════════════════════════════

    public function ficha(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Ficha::class, 'Id_Ficha', 'Id_Ficha');
    }

    public function juicios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JuicioEvaluativo::class, 'Id_Aprendiz', 'Id_Aprendiz');
    }

    // ═══════════════════════════════════════════════════════════
    //  LOCAL SCOPES — Reutilizables en cualquier controlador
    // ═══════════════════════════════════════════════════════════

    /**
     * Aprendices actualmente en formación.
     * Uso: Aprendiz::enFormacion()->count()
     */
    public function scopeEnFormacion(Builder $query): Builder
    {
        return $query->where('Estado', 'EN FORMACION');
    }

    /**
     * Aprendices en retiro voluntario.
     * Uso: Aprendiz::enRetiro()->get()
     */
    public function scopeEnRetiro(Builder $query): Builder
    {
        return $query->where('Estado', 'RETIRO VOLUNTARIO');
    }

    /**
     * Aprendices trasladados.
     * Uso: Aprendiz::trasladados()->count()
     */
    public function scopeTrasladados(Builder $query): Builder
    {
        return $query->where('Estado', 'TRASLADADO');
    }

    /**
     * Filtrar por ficha específica.
     * Uso: Aprendiz::deFicha(2892345)->get()
     */
    public function scopeDeFicha(Builder $query, int|string $fichaId): Builder
    {
        return $query->where('Id_Ficha', $fichaId);
    }

    /**
     * Búsqueda de texto en nombre, apellido, nombre completo, documento o tarjeta.
     * Uso: Aprendiz::buscar('Juan')->get()
     */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        $termino = trim($termino);
        if ($termino === '') {
            return $query;
        }

        $isPgsql = config('database.default') === 'pgsql';
        $operator = $isPgsql ? 'ilike' : 'like';

        return $query->where(function (Builder $q) use ($termino, $operator) {
            $q->where('Nombre', $operator, "%{$termino}%")
              ->orWhere('Apellido', $operator, "%{$termino}%")
              ->orWhere('Documento', $operator, "%{$termino}%")
              ->orWhere('Tipo_Documento', $operator, "%{$termino}%")
              ->orWhereRaw("CONCAT(\"Nombre\", ' ', \"Apellido\") {$operator} ?", ["%{$termino}%"])
              ->orWhereRaw("CONCAT(\"Apellido\", ' ', \"Nombre\") {$operator} ?", ["%{$termino}%"]);
        });
    }

    /**
     * Aprendices "en riesgo": ≥70% de sus juicios siguen pendientes.
     * Implementado con whereRaw para compatibilidad PostgreSQL estricto.
     * Uso: Aprendiz::enRiesgo()->with('ficha')->get()
     */
    public function scopeEnRiesgo(Builder $query): Builder
    {
        // Subconsultas correlacionadas — compatibles con PostgreSQL, e incluye conteos para las vistas
        return $query->withCount([
            'juicios as total_juicios',
            'juicios as pendientes_count' => fn (Builder $q) => $q->where('Estado', 0),
        ])->whereRaw(
            '(SELECT COUNT(*) FROM juicios_evaluativos j WHERE j."Id_Aprendiz" = aprendiz."Id_Aprendiz") > 0'
        )->whereRaw(
            '(SELECT COUNT(*) FROM juicios_evaluativos j WHERE j."Id_Aprendiz" = aprendiz."Id_Aprendiz" AND j."Estado" = 0) * 100'
            . ' / (SELECT COUNT(*) FROM juicios_evaluativos j WHERE j."Id_Aprendiz" = aprendiz."Id_Aprendiz") >= 70'
        );
    }

    /**
     * Eager loading estándar para el perfil completo (evita N+1).
     * Uso: Aprendiz::conExpediente()->findOrFail($id)
     */
    public function scopeConExpediente(Builder $query): Builder
    {
        return $query->with([
            'ficha.programa',
            'juicios.resultado.competencia',
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  ACCESSORS — Atributos calculados
    // ═══════════════════════════════════════════════════════════

    /**
     * Devuelve las iniciales del aprendiz (para avatares).
     * Uso: $aprendiz->iniciales → "JP"
     */
    public function getInicialesAttribute(): string
    {
        return strtoupper(
            substr($this->Nombre, 0, 1) . substr($this->Apellido, 0, 1)
        );
    }

    /**
     * Nombre completo formateado.
     * Uso: $aprendiz->nombre_completo → "Juan Pérez"
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->Nombre} {$this->Apellido}";
    }
}
