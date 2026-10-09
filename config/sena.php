<?php

/*
|--------------------------------------------------------------------------
| Configuración propia del Sistema de Juicios Evaluativos
|--------------------------------------------------------------------------
|
| Todo valor que antes estaba escrito directamente en el código (correos,
| credenciales, umbrales) vive aquí y se controla desde el archivo .env.
| Importante: nunca llames a env() fuera de los archivos de config/, porque
| deja de funcionar cuando se ejecuta `php artisan config:cache`.
|
*/

return [

    /*
    | Usuario administrador inicial (lo crea AdminSeeder solo si NO existe).
    | Si ADMIN_PASSWORD está vacío, el seeder genera una clave aleatoria y la
    | muestra una única vez en la consola.
    */
    'admin' => [
        'name'     => env('ADMIN_NAME', 'Administrador SENA'),
        'email'    => env('ADMIN_EMAIL', 'admin@sena.edu.co'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    | Protección de fuerza bruta en el login.
    */
    'login' => [
        'max_intentos_por_correo' => 5,   // intentos fallidos por correo+IP
        'max_intentos_por_ip'     => 20,  // intentos fallidos por IP
        'bloqueo_segundos'        => 60,
    ],

];
