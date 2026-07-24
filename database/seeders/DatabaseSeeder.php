<?php

namespace Database\Seeders;

use App\Models\Programa;
use App\Models\Resultado;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Aprendiz;
use App\Models\Funcionario;
use App\Models\JuicioEvaluativo;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Programa
        $prog = Programa::create([
            'Nombre' => 'Análisis y Desarrollo de Software',
            'Modalidad' => 'Virtual',
            'Codigo' => '228106',
            'Version' => '1'
        ]);

        // 2. Resultados y Competencias
        for ($i = 1; $i <= 3; $i++) {
            $res = Resultado::create(['Codigo' => 1000 + $i]);
            Competencia::create([
                'Codigo' => 'COMP-00' . $i,
                'Id_Resultado' => $res->Id_Resultado
            ]);
        }

        // 3. Funcionario (Instructor)
        $inst = Funcionario::create([
            'Tipo_Documento' => 'CC',
            'Documento' => 12345678,
            'Nombre' => 'Juan',
            'Apellido' => 'Pérez'
        ]);

        // 4. Ficha
        $ficha = Ficha::create([
            'Id_Ficha' => 2828282,
            'Jornada' => 'Mañana',
            'Id_Programa' => $prog->Id_Programa,
            'Id_Competencia' => Competencia::first()->Id_Competencia
        ]);

        // 5. Aprendices y Juicios
        $nombres = ['Camilo', 'Elena', 'Andrés', 'Sofía'];
        foreach ($nombres as $nombre) {
            $aprendiz = Aprendiz::create([
                'Tipo_Documento' => 'CC',
                'Documento' => rand(100000, 999999),
                'Nombre' => $nombre,
                'Apellido' => 'García',
                'Estado' => 'Activo',
                'Id_Ficha' => $ficha->Id_Ficha
            ]);

            // Crear algunos juicios aprobados (1) y pendientes (0)
            JuicioEvaluativo::create([
                'Id_Resultado' => Resultado::all()->random()->Id_Resultado,
                'Id_Aprendiz' => $aprendiz->Id_Aprendiz,
                'Estado' => rand(0, 1),
                'Id_Funcionario' => $inst->Id_Funcionario,
                'Fecha' => now()
            ]);
        }
    }
}
