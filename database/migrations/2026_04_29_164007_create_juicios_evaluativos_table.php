<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('juicios_evaluativos', function (Blueprint $table) {
            $table->id('Id_Juicio');
            $table->unsignedBigInteger('Id_Resultado');
            $table->unsignedBigInteger('Id_Aprendiz');
            $table->integer('Estado');
            $table->unsignedBigInteger('Id_Funcionario');
            $table->date('Fecha')->nullable();
            $table->timestamp('Hora')->nullable();
            $table->timestamps();

            $table->foreign('Id_Resultado')->references('Id_Resultado')->on('resultados');
            $table->foreign('Id_Aprendiz')->references('Id_Aprendiz')->on('aprendiz');
            $table->foreign('Id_Funcionario')->references('Id_Funcionario')->on('funcionario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juicios_evaluativos');
    }
};
