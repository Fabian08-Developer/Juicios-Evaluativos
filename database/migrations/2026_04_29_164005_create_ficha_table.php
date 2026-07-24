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
        Schema::create('ficha', function (Blueprint $table) {
            $table->unsignedBigInteger('Id_Ficha')->primary();
            $table->string('Jornada');
            $table->unsignedBigInteger('Id_Programa');
            $table->timestamps();

            $table->foreign('Id_Programa')->references('Id_Programa')->on('programa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ficha');
    }
};
