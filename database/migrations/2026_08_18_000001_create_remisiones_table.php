<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remisiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Id_Aprendiz');
            $table->unsignedBigInteger('Id_Ficha')->nullable();
            $table->integer('score_riesgo')->default(0);
            $table->string('nivel_semaforo', 20)->default('MODERADO'); // CRITICO, MODERADO, ESTABLE
            $table->integer('total_pendientes')->default(0);
            $table->string('estado_remision', 30)->default('PENDIENTE'); // PENDIENTE, EN_SEGUIMIENTO, ATENDIDO, CERRADO
            $table->string('radicado', 50)->nullable()->index();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('Id_Aprendiz')->references('Id_Aprendiz')->on('aprendiz')->onDelete('cascade');
            $table->foreign('Id_Ficha')->references('Id_Ficha')->on('ficha')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remisiones');
    }
};
