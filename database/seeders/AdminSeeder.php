<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea el usuario administrador inicial.
 *
 * Es IDEMPOTENTE: si el usuario ya existe no se toca (en particular, no se
 * restablece su contraseña), de modo que es seguro ejecutarlo en cada
 * despliegue. La contraseña sale de ADMIN_PASSWORD (.env); si no está definida
 * se genera una aleatoria y se muestra una sola vez en la consola.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $cfg = config('sena.admin');

        if (DB::table('users')->where('email', $cfg['email'])->exists()) {
            $this->command?->info("El usuario {$cfg['email']} ya existe: no se modificó su contraseña.");
            return;
        }

        $password = $cfg['password'] ?: Str::password(16, symbols: false);

        DB::table('users')->insert([
            'name'       => $cfg['name'],
            'email'      => $cfg['email'],
            'password'   => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info("Usuario administrador creado: {$cfg['email']}");

        if (! $cfg['password']) {
            $this->command?->warn("Contraseña generada (guárdala, no se volverá a mostrar): {$password}");
        }
    }
}
