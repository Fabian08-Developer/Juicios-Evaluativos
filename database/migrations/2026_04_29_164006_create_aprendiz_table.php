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
        Schema::create('aprendiz', function (Blueprint $table) {
            $table->id('Id_Aprendiz');
            $table->string('Tipo_Documento');
            $table->string('Documento')->unique();
            $table->string('Nombre');
            $table->string('Apellido');
            $table->string('Estado');
            $table->integer('Id_Ficha');
            $table->timestamps();

            $table->foreign('Id_Ficha')->references('Id_Ficha')->on('ficha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprendiz');
    }
};
