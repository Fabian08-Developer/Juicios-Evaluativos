<?php
namespace Tests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Los reportes que esperan decisión se guardan en el disco local: en pruebas, uno falso.
        Storage::fake('local');
    }
}
