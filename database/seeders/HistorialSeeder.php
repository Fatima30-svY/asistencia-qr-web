<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HistorialSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42); // resultados repetibles

        $grupo = Grupo::first();
        $materia = Materia::first();
        $docente = User::where('rol', 'docente')->first();
        $alumnos = User::where('rol', 'alumno')->orderBy('matricula')->get();

        // Perfil de cada alumno: [prob. de falta, prob. de retardo, extra de falta en viernes]
        $perfiles = [
            'A001' => [0.03, 0.05, 0.00], // puntual
            'A002' => [0.08, 0.40, 0.00], // llega tarde seguido
            'A003' => [0.15, 0.10, 0.35], // falta mucho los viernes
            'A004' => [0.40, 0.15, 0.10], // faltista
            'A005' => [0.12, 0.10, 0.00], // regular
        ];


        $horarios = [['08:00:00', '08:50:00'], ['13:00:00', '13:50:00']];
        $ultimoEstado = [];

        $dia = Carbon::today()->subDays(42);

        while ($dia->lt(Carbon::today())) {
            if (! $dia->isWeekend()) {
                foreach ($horarios as [$horaInicio, $horaFin]) {
                    $clase = Clase::create([
                        'materia_id' => $materia->id,
                        'grupo_id' => $grupo->id,
                        'docente_id' => $docente->id,
                        'fecha' => $dia->toDateString(),
                        'hora_inicio' => $horaInicio,
                        'hora_fin' => $horaFin,
                        'tolerancia_min' => 10,
                    ]);

                    $inicio = Carbon::parse($dia->toDateString().' '.$horaInicio);

                    foreach ($alumnos as $alumno) {
                        [$pFalta, $pRetardo, $extraViernes] = $perfiles[$alumno->matricula];

                        $p = $pFalta;
                        if ($dia->isFriday()) {
                            $p += $extraViernes;
                        }
                        if ($horaInicio === '08:00:00') {
                            $p += 0.05; // a primera hora se falta un poco más
                        }
                        if (($ultimoEstado[$alumno->id] ?? null) === 'falta') {
                            $p += 0.30; // las faltas vienen en racha

                        }
                        $p = min($p, 0.90);

                        $r = mt_rand() / mt_getrandmax();

                        if ($r < $p) {
                            $estado = mt_rand(1, 100) <= 20 ? 'justificada' : 'falta';
                            $escaneo = null;
                        } elseif ($r < $p + $pRetardo) {
                            $estado = 'retardo';
                            $escaneo = $inicio->copy()->addMinutes(15);
                        } else {
                            $estado = 'asistencia';
                            $escaneo = $inicio->copy()->addMinutes(3);
                        }

                        Asistencia::create([
                            'clase_id' => $clase->id,
                            'alumno_id' => $alumno->id,
                            'escaneado_at' => $escaneo,
                            'estado' => $estado,
                        ]);

                        $ultimoEstado[$alumno->id] = $estado;
                    }
                }
            }

            $dia->addDay();
        }
    }
}