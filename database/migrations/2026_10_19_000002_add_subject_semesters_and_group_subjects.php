<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->unsignedTinyInteger('semestre')->nullable();
        });
        Schema::create('grupo_materia', function (Blueprint $table) {
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->primary(['grupo_id', 'materia_id']);
        });
        foreach (DB::table('clases')->select('grupo_id', 'materia_id')->distinct()->get() as $clase) {
            DB::table('grupo_materia')->insertOrIgnore(['grupo_id' => $clase->grupo_id, 'materia_id' => $clase->materia_id]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_materia');
        Schema::table('materias', fn (Blueprint $table) => $table->dropColumn('semestre'));
    }
};
