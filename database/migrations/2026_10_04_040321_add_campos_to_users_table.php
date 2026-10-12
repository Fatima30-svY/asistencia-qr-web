<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'docente', 'alumno'])->default('alumno');
            $table->string('matricula')->nullable()->unique();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grupo_id');
            $table->dropColumn(['rol', 'matricula']);
        });
    }
};