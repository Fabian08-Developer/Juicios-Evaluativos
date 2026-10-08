<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Comando: php artisan sena:cambiar-clave {email?}
 *
 * Cambia la contraseña de un usuario sin pasar por tinker ni dejar la clave en
 * el historial del shell. Úsalo, por ejemplo, para reemplazar la contraseña
 * por defecto antigua del administrador en producción.
 */
class CambiarClaveUsuario extends Command
{
    protected $signature   = 'sena:cambiar-clave {email? : Correo del usuario (por defecto, el administrador configurado)}';
    protected $description = 'Cambia la contraseña de un usuario del sistema.';

    public function handle(): int
    {
        $email = $this->argument('email') ?: config('sena.admin.email');
        $user  = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No existe un usuario con el correo {$email}.");
            return self::FAILURE;
        }

        $clave = (string) $this->secret('Nueva contraseña (mínimo 12 caracteres)');
        if (mb_strlen($clave) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres.');
            return self::FAILURE;
        }
        if ($clave !== (string) $this->secret('Repite la contraseña')) {
            $this->error('Las contraseñas no coinciden.');
            return self::FAILURE;
        }

        $user->password = $clave; // el cast "hashed" del modelo la cifra
        $user->save();

        $this->info("Contraseña actualizada para {$email}.");
        return self::SUCCESS;
    }
}
