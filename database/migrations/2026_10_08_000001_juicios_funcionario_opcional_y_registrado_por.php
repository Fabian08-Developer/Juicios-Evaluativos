<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1) Id_Funcionario pasa a ser opcional.
 *    Sofia Plus no asigna funcionario a los juicios "POR EVALUAR" (viene "-").
 *    Antes el importador inventaba un funcionario falso para cumplir el NOT NULL.
 *
 * 2) registrado_por: usuario del sistema que calificó el juicio manualmente en la
 *    matriz. Antes toda calificación manual se atribuía al primer funcionario de
 *    la tabla. También permite distinguir una aprobación local de una oficial
 *    (la importación no pisa las aprobaciones locales con "POR EVALUAR").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('juicios_evaluativos', function (Blueprint $table) {
            $table->unsignedBigInteger('Id_Funcionario')->nullable()->change();
            $table->unsignedBigInteger('registrado_por')->nullable()->after('Id_Funcionario');
            $table->foreign('registrado_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('juicios_evaluativos', function (Blueprint $table) {
            $table->dropForeign(['registrado_por']);
            $table->dropColumn('registrado_por');
            // Id_Funcionario se deja opcional a propósito: volver a NOT NULL
            // fallaría si ya existen juicios sin funcionario.
        });
    }
};
