<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Excepcion;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExcepcionController extends Controller
{
    private const TIPOS_ALUMNO = ['justificante', 'permiso'];
    private const TIPOS_GRUPO = ['suspension', 'festivo'];

    public function index()
    {
        $usuario = auth()->user();
        $alumnos = $this->alumnosPermitidos($usuario);

        $consulta = Excepcion::with(['alumno', 'grupo', 'registradaPor'])
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->take(50);

        // El docente ve las de sus alumnos y las generales de sus grupos
        if ($usuario->rol === 'docente') {
            $alumnoIds = $alumnos->pluck('id');
            $grupoIds = $this->gruposDelDocente($usuario);

            $consulta->where(function ($q) use ($alumnoIds, $grupoIds) {

                $q->whereIn('alumno_id', $alumnoIds)
                  ->orWhere(function ($q2) use ($grupoIds) {
                      $q2->whereNull('alumno_id')
                         ->where(function ($q3) use ($grupoIds) {
                             $q3->whereNull('grupo_id')->orWhereIn('grupo_id', $grupoIds);
                         });
                  });
            });
        }

        return view('excepciones.index', [
            'excepciones' => $consulta->get(),
            'alumnos' => $alumnos,
            'grupos' => $usuario->rol === 'admin' ? Grupo::orderBy('nombre')->get() : collect(),
            'tipos' => $this->tiposPermitidos($usuario),
        ]);
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();

        $datos = $request->validate([
            'tipo' => ['required', Rule::in($this->tiposPermitidos($usuario))],
            'alumno_id' => ['nullable', 'integer'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        if (in_array($datos['tipo'], self::TIPOS_ALUMNO, true)) {

            // Justificante o permiso: debe ser un alumno que el usuario pueda gestionar
            $alumno = $this->alumnosPermitidos($usuario)->firstWhere('id', (int) ($datos['alumno_id'] ?? 0));

            if (! $alumno) {
                return back()->withErrors(['alumno_id' => 'Selecciona un alumno válido.'])->withInput();
            }

            $datos['grupo_id'] = null;
        } else {
            // Suspensión o festivo: aplica a un grupo, o a toda la escuela si no se elige grupo
            $datos['alumno_id'] = null;
        }

        Excepcion::create($datos + ['registrada_por' => $usuario->id]);

        return redirect()->route('excepciones.index')->with('ok', 'Excepción registrada.');
    }

    public function destroy(Excepcion $excepcion)
    {
        $usuario = auth()->user();

        abort_unless(
            $usuario->rol === 'admin' || $excepcion->registrada_por === $usuario->id,
            403
        );

        $excepcion->delete();

        return redirect()->route('excepciones.index')->with('ok', 'Excepción eliminada.');
    }


    private function tiposPermitidos(User $usuario): array
    {
        return $usuario->rol === 'admin'
            ? array_merge(self::TIPOS_ALUMNO, self::TIPOS_GRUPO)
            : self::TIPOS_ALUMNO;
    }

    private function gruposDelDocente(User $usuario): array
    {
        return Clase::where('docente_id', $usuario->id)
            ->pluck('grupo_id')
            ->unique()
            ->values()
            ->all();
    }

    private function alumnosPermitidos(User $usuario)
    {
        $consulta = User::where('rol', 'alumno')->orderBy('name');

        if ($usuario->rol === 'docente') {
            $consulta->whereIn('grupo_id', $this->gruposDelDocente($usuario));
        }

        return $consulta->get();
    }
}