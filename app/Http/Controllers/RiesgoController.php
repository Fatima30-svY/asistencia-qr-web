<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\User;
use App\Services\ClasificadorBayesiano;

class RiesgoController extends Controller
{
    public function index(ClasificadorBayesiano $clasificador)
    {
        $modeloListo = $clasificador->tieneModelo();
        $filas = collect();

        if ($modeloListo) {
            $grupoIds = Clase::where('docente_id', auth()->id())->pluck('grupo_id');
            $alumnos = User::where('rol', 'alumno')
                ->when(auth()->user()->rol === 'docente', fn ($query) => $query->whereIn('grupo_id', $grupoIds))->get();

            foreach ($alumnos as $alumno) {
                // Contexto: la clase más reciente de su grupo (día y horario)
                $clase = Clase::where('grupo_id', $alumno->grupo_id)
                    ->when(auth()->user()->rol === 'docente', fn ($query) => $query->where('docente_id', auth()->id()))
                    ->orderByDesc('fecha')
                    ->orderByDesc('hora_inicio')
                    ->first();

                if (! $clase) {
                    continue;
                }

                $evaluacion = $clasificador->evaluar($alumno, $clase->fecha, $clase->hora_inicio);

                if ($evaluacion) {
                    $filas->push(['alumno' => $alumno] + $evaluacion);
                }
            }

            $filas = $filas->sortByDesc('probabilidad')->values();
        }

        return view('riesgo.index', compact('filas', 'modeloListo'));
    }

    public function entrenar(ClasificadorBayesiano $clasificador)
    {
        $clasificador->entrenar();

        return redirect()->route('riesgo.index')->with('ok', 'Modelo reentrenado con el historial actual.');
    }
}
