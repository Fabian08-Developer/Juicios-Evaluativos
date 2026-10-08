<?php

namespace Tests\Unit;

use App\Services\RiesgoDesercionService;
use Tests\TestCase;

class RiesgoDesercionServiceTest extends TestCase
{
    private function evaluar(?string $estado, int $total, int $pendientes): array
    {
        return (new RiesgoDesercionService())->evaluar($estado, $total, $pendientes);
    }

    public function test_72_por_ciento_pendiente_es_critico_con_score_85(): void
    {
        // Antes: la pantalla mostraba 85 y la remisión guardaba 72.
        $r = $this->evaluar('EN FORMACION', 25, 18);
        $this->assertSame(85, $r['score']);
        $this->assertSame('critico', $r['nivel']);
    }

    public function test_umbrales(): void
    {
        $this->assertSame('estable', $this->evaluar('EN FORMACION', 100, 10)['nivel']);
        $this->assertSame('moderado', $this->evaluar('EN FORMACION', 100, 40)['nivel']);
        $this->assertSame('moderado', $this->evaluar('EN FORMACION', 100, 69)['nivel']);
        $this->assertSame('critico', $this->evaluar('EN FORMACION', 100, 70)['nivel']);
    }

    public function test_estados_terminales_son_100(): void
    {
        foreach (['RETIRO VOLUNTARIO', 'CANCELADO', 'TRASLADADO'] as $estado) {
            $r = $this->evaluar($estado, 10, 0);
            $this->assertSame(100, $r['score']);
            $this->assertSame('critico', $r['nivel']);
        }
    }

    public function test_sin_juicios_no_divide_por_cero(): void
    {
        $r = $this->evaluar('EN FORMACION', 0, 0);
        $this->assertSame(0, $r['score']);
        $this->assertSame('estable', $r['nivel']);
    }
}
