<?php

namespace Database\Seeders;

use App\Models\Clase;
use App\Models\Excepcion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $grupo = Grupo::create(['nombre' => '3A', 'semestre' => 3]);
        $materia = Materia::create(['nombre' => 'Programación']);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'password',
            'rol' => 'admin',
        ]);

        $docente = User::create([
            'name' => 'Docente Prueba',
            'email' => 'docente@test.com',
            'password' => 'password',
            'rol' => 'docente',
        ]);

        $alumnos = [];
        foreach (range(1, 5) as $i) {
            $alumnos[$i] = User::create([
                'name' => "Alumno $i",
                'email' => "alumno$i@test.com",
                'password' => 'password',
                'rol' => 'alumno',
                'matricula' => 'A00'.$i,
                'grupo_id' => $grupo->id,
            ]);
        }

        // Clase de hoy: empezó hace 5 minutos y termina en 45
        Clase::create([
            'materia_id' => $materia->id,
            'grupo_id' => $grupo->id,
            'docente_id' => $docente->id,
            'fecha' => now()->toDateString(),
            'hora_inicio' => now()->subMinutes(5)->format('H:i:s'),
            'hora_fin' => now()->addMinutes(45)->format('H:i:s'),
            'tolerancia_min' => 10,
            'token_qr' => Str::random(32),
            'qr_expira_at' => now()->addMinutes(45),
        ]);

        // Excepción: el alumno 5 tiene justificante hoy
        Excepcion::create([
            'tipo' => 'justificante',
            'alumno_id' => $alumnos[5]->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->toDateString(),
            'motivo' => 'Cita médica',
        ]);
        $this->call(HistorialSeeder::class);
    }
}