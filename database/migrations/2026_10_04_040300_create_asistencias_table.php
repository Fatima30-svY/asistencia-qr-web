<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases');
            $table->foreignId('alumno_id')->constrained('users');
            $table->timestamp('escaneado_at')->nullable();
            $table->enum('estado', ['asistencia', 'retardo', 'falta', 'justificada']);
            $table->timestamps();

            $table->unique(['clase_id', 'alumno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};