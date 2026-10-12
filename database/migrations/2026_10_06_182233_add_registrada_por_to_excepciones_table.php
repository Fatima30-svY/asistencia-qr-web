<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excepciones', function (Blueprint $table) {
            $table->foreignId('registrada_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('excepciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registrada_por');
        });
    }
};