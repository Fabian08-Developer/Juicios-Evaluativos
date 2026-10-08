<?php

namespace Tests\Feature;

use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\JuicioEvaluativo;
use App\Models\Programa;
use Illuminate\Support\Facades\DB;
use App\Models\Resultado;
use App\Models\Competencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FichasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Programa $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->programa = Programa::create(['Nombre' => 'ADSO', 'Modalidad' => 'PRESENCIAL', 'Codigo' => '228118', 'Version' => '1']);
    }

    private function ficha(int $id = 2828282): Ficha
    {
        return Ficha::create(['Id_Ficha' => $id, 'Jornada' => 'DIURNA', 'Id_Programa' => $this->programa->Id_Programa]);
    }

    private function aprendizConJuicio(Ficha $ficha, string $doc = '1000000001'): Aprendiz
    {
        $ap = Aprendiz::create(['Tipo_Documento' => 'CC', 'Documento' => $doc, 'Nombre' => 'A', 'Apellido' => 'B', 'Estado' => 'EN FORMACION', 'Id_Ficha' => $ficha->Id_Ficha]);
        $comp = Competencia::firstOrCreate(['Codigo' => 'C1'], ['Nombre' => 'c']);
        $res = Resultado::firstOrCreate(['Codigo' => 'R1'], ['Nombre' => 'r', 'Id_Competencia' => $comp->Id_Competencia]);
        JuicioEvaluativo::create(['Id_Resultado' => $res->Id_Resultado, 'Id_Aprendiz' => $ap->Id_Aprendiz, 'Estado' => 0]);

        return $ap;
    }

    public function test_crear_ficha_funciona(): void
    {
        // Antes: 500 "column Id_Competencia of relation ficha does not exist".
        $this->actingAs($this->user)
            ->post(route('fichas.store'), ['Id_Ficha' => 3142784, 'Id_Programa' => $this->programa->Id_Programa])
            ->assertRedirect(route('fichas.index'));

        $this->assertSame('DIURNA', Ficha::find(3142784)->Jornada);
    }

    public function test_crear_ficha_valida_los_datos(): void
    {
        $this->ficha(2828282);

        $this->actingAs($this->user)->post(route('fichas.store'), ['Id_Ficha' => 2828282, 'Id_Programa' => $this->programa->Id_Programa])
            ->assertSessionHasErrors('Id_Ficha');          // duplicada
        $this->actingAs($this->user)->post(route('fichas.store'), ['Id_Ficha' => 99999999999, 'Id_Programa' => $this->programa->Id_Programa])
            ->assertSessionHasErrors('Id_Ficha');          // no cabe en aprendiz.Id_Ficha (integer)
        $this->actingAs($this->user)->post(route('fichas.store'), ['Id_Ficha' => 111, 'Id_Programa' => 9999])
            ->assertSessionHasErrors('Id_Programa');
        $this->actingAs($this->user)->post(route('fichas.store'), ['Id_Ficha' => 111, 'Id_Programa' => $this->programa->Id_Programa, 'Jornada' => 'INVENTADA'])
            ->assertSessionHasErrors('Jornada');
    }

    public function test_las_pantallas_de_fichas_cargan_y_show_ya_no_existe(): void
    {
        $f = $this->ficha();

        $this->actingAs($this->user)->get(route('fichas.index'))->assertOk();
        $this->actingAs($this->user)->get(route('fichas.create'))->assertOk();
        $this->actingAs($this->user)->get(route('fichas.edit', $f->Id_Ficha))->assertOk();
        $this->actingAs($this->user)->get('/fichas/' . $f->Id_Ficha)->assertStatus(405); // antes: 500 (método show inexistente)
    }

    public function test_el_numero_de_ficha_no_se_puede_cambiar_pero_el_programa_si(): void
    {
        $f = $this->ficha();
        $this->aprendizConJuicio($f);
        $otro = Programa::create(['Nombre' => 'OTRO', 'Modalidad' => 'P', 'Codigo' => '9', 'Version' => '1']);

        // Antes: intentar cambiar el número daba 500 por llave foránea.
        $this->actingAs($this->user)
            ->put(route('fichas.update', $f->Id_Ficha), ['Id_Ficha' => 999999, 'Id_Programa' => $otro->Id_Programa])
            ->assertRedirect(route('fichas.index'));

        $this->assertNull(Ficha::find(999999));
        $this->assertSame($otro->Id_Programa, Ficha::find($f->Id_Ficha)->Id_Programa);
        $this->assertSame(1, Aprendiz::where('Id_Ficha', $f->Id_Ficha)->count());
    }

    public function test_eliminar_una_ficha_borra_aprendices_y_juicios(): void
    {
        $f = $this->ficha();
        $otra = $this->ficha(3142784);
        $this->aprendizConJuicio($f);
        $this->aprendizConJuicio($otra, '1000000002');
        Cache::put('dashboard.stats.ficha.2828282', ['x'], 300);

        $this->actingAs($this->user)->delete(route('fichas.destroy', $f->Id_Ficha))
            ->assertRedirect(route('fichas.index'))->assertSessionHas('success');

        $this->assertNull(Ficha::find($f->Id_Ficha));
        $this->assertSame(1, Aprendiz::count(), 'la otra ficha no se toca');
        $this->assertSame(1, JuicioEvaluativo::count());
        $this->assertFalse(Cache::has('dashboard.stats.ficha.2828282'));
    }

    public function test_eliminar_una_ficha_con_remisiones_historicas_no_falla(): void
    {
        // El módulo de remisiones se retiró, pero la tabla (con datos antiguos) sigue en la BD.
        $f = $this->ficha();
        $ap = $this->aprendizConJuicio($f);
        DB::table('remisiones')->insert([
            'Id_Aprendiz' => $ap->Id_Aprendiz, 'Id_Ficha' => $f->Id_Ficha, 'score_riesgo' => 85, 'nivel_semaforo' => 'CRITICO',
            'total_pendientes' => 1, 'estado_remision' => 'PENDIENTE', 'radicado' => 'REM-2026-0001', 'motivo' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->user)->delete(route('fichas.destroy', $f->Id_Ficha))
            ->assertRedirect(route('fichas.index'))->assertSessionHas('success');

        $this->assertNull(Ficha::find($f->Id_Ficha));
        $this->assertSame(0, Aprendiz::count());
        $this->assertSame(0, DB::table('remisiones')->count(), 'las remisiones del aprendiz se van con él (cascada de la BD)');
    }
}
