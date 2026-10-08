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
    | Correo institucional de Bienestar al Aprendiz / Coordinación Académica
    | al que se envían las alertas. Si está vacío, la alerta se registra pero
    | NO se envía correo (y el sistema se lo informa al usuario).
    */
    'bienestar_email' => env('BIENESTAR_EMAIL'),

    /*
    | Protección de fuerza bruta en el login.
    */
    'login' => [
        'max_intentos_por_correo' => 5,   // intentos fallidos por correo+IP
        'max_intentos_por_ip'     => 20,  // intentos fallidos por IP
        'bloqueo_segundos'        => 60,
    ],

    /*
    | Semáforo de riesgo de deserción (ver App\Services\RiesgoDesercionService).
    | El score es el % de juicios pendientes, con estas reglas:
    |   - estados terminales (retiro, cancelado, trasladado) => 100
    |   - % pendientes >= umbral_alerta => score mínimo 'score_alerta'
    |   - score >= critico => CRÍTICO; >= moderado => MODERADO; si no, ESTABLE
    */
    'riesgo' => [
        'umbral_alerta' => 70,
        'score_alerta'  => 85,
        'critico'       => 75,
        'moderado'      => 40,
    ],

    /*
    | Importación de reportes de Sofia Plus.
    |
    | conservar_aprobados_locales: si un juicio fue aprobado manualmente desde
    | la matriz del sistema y el Excel (todavía) dice "POR EVALUAR", se conserva
    | la aprobación local en lugar de borrarla. Una aprobación que SÍ viene en
    | el Excel siempre prevalece.
    */
    'importacion' => [
        'conservar_aprobados_locales' => true,
    ],

];
