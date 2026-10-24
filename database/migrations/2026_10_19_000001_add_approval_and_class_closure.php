<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('aprobado_at')->nullable();
        });
        Schema::table('clases', function (Blueprint $table) {
            $table->timestamp('cerrada_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clases', fn (Blueprint $table) => $table->dropColumn('cerrada_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('aprobado_at'));
    }
};
