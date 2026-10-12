<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepciones', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['justificante', 'suspension', 'festivo', 'permiso']);
            $table->foreignId('alumno_id')->nullable()->constrained('users');
            $table->foreignId('grupo_id')->nullable()->constrained('grupos');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepciones');
    }
};