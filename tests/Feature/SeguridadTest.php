<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    // ── AdminSeeder ───────────────────────────────────────────────────────

    public function test_el_seeder_no_restablece_la_contrasena_de_un_admin_existente(): void
    {
        config(['sena.admin.password' => 'ClaveInicial#2026x']);
        (new AdminSeeder())->run();

        DB::table('users')->where('email', 'admin@sena.edu.co')
            ->update(['password' => Hash::make('MiClaveNueva#2026')]);

        (new AdminSeeder())->run(); // lo que antes hacía cada despliegue

        $hash = DB::table('users')->where('email', 'admin@sena.edu.co')->value('password');
        $this->assertTrue(Hash::check('MiClaveNueva#2026', $hash));
        $this->assertFalse(Hash::check('Sena2026*', $hash), 'ya no existe una clave por defecto');
        $this->assertSame(1, User::count());
    }

    public function test_el_seeder_usa_la_clave_configurada(): void
    {
        config(['sena.admin.password' => 'ClaveDelEnv#2026']);
        (new AdminSeeder())->run();

        $this->assertTrue(Hash::check('ClaveDelEnv#2026', User::first()->password));
    }

    public function test_el_seeder_sin_clave_configurada_genera_una_aleatoria(): void
    {
        config(['sena.admin.password' => null]);
        (new AdminSeeder())->run();

        $hash = User::first()->password;
        $this->assertFalse(Hash::check('Sena2026*', $hash));
        $this->assertFalse(Hash::check('', $hash));
    }

    public function test_comando_cambiar_clave(): void
    {
        $user = User::factory()->create(['email' => 'admin@sena.edu.co']);

        $this->artisan('sena:cambiar-clave', ['email' => 'admin@sena.edu.co'])
            ->expectsQuestion('Nueva contraseña (mínimo 12 caracteres)', 'OtraClaveSegura#1')
            ->expectsQuestion('Repite la contraseña', 'OtraClaveSegura#1')
            ->assertExitCode(0);

        $this->assertTrue(Hash::check('OtraClaveSegura#1', $user->fresh()->password));
    }

    public function test_comando_cambiar_clave_rechaza_claves_cortas(): void
    {
        User::factory()->create(['email' => 'admin@sena.edu.co']);

        $this->artisan('sena:cambiar-clave', ['email' => 'admin@sena.edu.co'])
            ->expectsQuestion('Nueva contraseña (mínimo 12 caracteres)', 'corta')
            ->assertExitCode(1);
    }

    // ── Login: fuerza bruta ───────────────────────────────────────────────

    public function test_el_login_se_bloquea_tras_varios_intentos_fallidos(): void
    {
        $user = User::factory()->create(['password' => 'ClaveCorrecta#2026']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => "mala$i"])
                ->assertSessionHasErrors('email');
        }

        // Incluso con la contraseña CORRECTA, mientras dure el bloqueo no entra.
        $this->post('/login', ['email' => $user->email, 'password' => 'ClaveCorrecta#2026'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_un_login_correcto_dentro_del_limite_entra_y_reinicia_el_contador(): void
    {
        $user = User::factory()->create(['password' => 'ClaveCorrecta#2026']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => "mala$i"]);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'ClaveCorrecta#2026'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_el_limite_por_ip_frena_el_cambio_de_correos(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/login', ['email' => "persona{$i}@x.co", 'password' => 'mala']);
        }

        $this->post('/login', ['email' => 'otra@x.co', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
    }

    // ── Acceso y XSS ──────────────────────────────────────────────────────

    public function test_las_rutas_protegidas_redirigen_a_los_invitados(): void
    {
        foreach (['/', '/aprendices', '/fichas', '/importaciones', '/api/dashboard-stats', '/aprendices/buscar?q=ab', '/juicios'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        foreach (['/fichas', '/aprendices/importar'] as $url) {
            $this->post($url)->assertRedirect('/login');
        }
    }

    public function test_el_buscador_y_los_toasts_escapan_los_datos(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/aprendices/cargar')->getContent();

        // Regresión del XSS: nombre/documento/ficha/iniciales/url pasan por escapeHtml()
        // y el mensaje del toast se asigna con textContent.
        $this->assertStringContainsString('function escapeHtml', $html);
        foreach (['item.nombre', 'item.doc', 'item.ficha', 'item.iniciales', 'item.url'] as $campo) {
            $this->assertStringContainsString("escapeHtml({$campo})", $html);
            $this->assertStringNotContainsString('${' . $campo . '}', $html);
        }
        $this->assertStringContainsString('textEl.textContent = message', $html);
        $this->assertStringNotContainsString('<span>${message}</span>', $html);
    }
}
