<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial entre reportes.
 *
 * - importaciones.user_id: quién subió el reporte.
 * - importaciones.resumen: foto de la ficha tras la carga (conteos por estado,
 *   aprobados/pendientes y cantidad de cambios por tipo).
 * - importacion_cambios: qué cambió frente a la carga anterior de la ficha
 *   (solo los cambios, no una copia completa del reporte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id_ficha');
            $table->json('resumen')->nullable()->after('detalle');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('importacion_cambios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('importacion_id');
            $table->unsignedBigInteger('Id_Aprendiz')->nullable();
            $table->unsignedBigInteger('Id_Resultado')->nullable();
            $table->string('tipo', 30);
            $table->string('valor_anterior', 100)->nullable();
            $table->string('valor_nuevo', 100)->nullable();

            $table->foreign('importacion_id')->references('id')->on('importaciones')->cascadeOnDelete();
            $table->foreign('Id_Aprendiz')->references('Id_Aprendiz')->on('aprendiz')->cascadeOnDelete();
            $table->foreign('Id_Resultado')->references('Id_Resultado')->on('resultados')->cascadeOnDelete();

            $table->index(['importacion_id', 'tipo']);
            $table->index('Id_Aprendiz');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacion_cambios');

        Schema::table('importaciones', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'resumen']);
        });
    }
};
