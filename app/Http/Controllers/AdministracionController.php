<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Excepcion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdministracionController extends Controller
{
    public function index(Request $request)
    {
        $seccion = $request->route('seccion') ?? ($request->filled('editar_clase') ? 'clases' : 'resumen');
        $filtros = $request->validate([
            'buscar_usuario' => ['nullable', 'string', 'max:255'],
            'rol' => ['nullable', Rule::in(['alumno', 'docente'])],
            'grupo' => ['nullable', 'integer', 'exists:grupos,id'],
            'buscar_materia' => ['nullable', 'string', 'max:255'],
            'semestre_materia' => ['nullable', 'integer', 'between:1,255'],
        ]);

        return view('admin.index', [
            'seccion' => $seccion,
            'totales' => [
                'docentes' => User::where('rol', 'docente')->count(),
                'alumnos' => User::where('rol', 'alumno')->count(),
                'materias' => Materia::count(),
                'grupos' => Grupo::count(),
                'pendientes' => User::where('rol', 'docente')->whereNull('aprobado_at')->count(),
            ],
            'usuarios' => User::whereIn('rol', ['docente', 'alumno'])->with('grupo')
                ->when(in_array($seccion, ['docentes', 'alumnos']), fn ($query) => $query->where('rol', $seccion === 'docentes' ? 'docente' : 'alumno'))
                ->when($filtros['buscar_usuario'] ?? null, fn ($query, $texto) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$texto.'%')->orWhere('email', 'like', '%'.$texto.'%')->orWhere('matricula', 'like', '%'.$texto.'%')))
                ->when(! in_array($seccion, ['docentes', 'alumnos']) && ($filtros['rol'] ?? null), fn ($query) => $query->where('rol', $filtros['rol']))
                ->when($filtros['grupo'] ?? null, fn ($query, $grupo) => $query->where('grupo_id', $grupo))
                ->orderBy('name')->paginate(20, ['*'], 'usuarios_page')->withQueryString(),
            'grupos' => Grupo::withCount(['alumnos', 'clases'])->orderBy('nombre')->get(),
            'materias' => Materia::withCount('clases')
                ->when($filtros['buscar_materia'] ?? null, fn ($query, $texto) => $query->where(fn ($query) => $query->where('nombre', 'like', '%'.$texto.'%')->orWhere('clave', 'like', '%'.$texto.'%')))
                ->when($filtros['semestre_materia'] ?? null, fn ($query, $semestre) => $query->where('semestre', $semestre))
                ->orderBy('semestre')->orderBy('nombre')->paginate(20, ['*'], 'materias_page')->withQueryString(),
            'todasMaterias' => Materia::orderBy('nombre')->get(),
            'clases' => Clase::with(['grupo', 'materia', 'docente'])->orderByDesc('fecha')->get(),
            'docentes' => User::where('rol', 'docente')->whereNotNull('aprobado_at')->orderBy('name')->get(),
        ]);
    }

    public function nuevaMateria()
    {
        return view('admin.materia-form', ['materia' => new Materia]);
    }

    public function editarMateria(Materia $materia)
    {
        return view('admin.materia-form', compact('materia'));
    }

    public function usuario(Request $request, User $usuario)
    {
        abort_unless(in_array($usuario->rol, ['docente', 'alumno']), 403);
        $contacto = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
        ]);
        if ($usuario->rol === 'docente') {
            $request->validate(['aprobado' => ['sometimes', 'required', 'boolean']]);
            if ($request->has('aprobado')) {
                $usuario->aprobado_at = $request->boolean('aprobado') ? now() : null;
            }
        } else {
            $datos = $request->validate([
                'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
                'matricula' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($usuario->id)],
            ]);
            $usuario->fill($datos);
        }
        $usuario->fill($contacto);
        if ($usuario->isDirty('email')) {
            $usuario->email_verified_at = null;
        }
        $usuario->save();

        return back()->with('ok', 'Usuario actualizado.');
    }

    public function guardar(Request $request, string $tipo, ?int $id = null)
    {
        $model = $this->modelo($tipo);
        $registro = $id ? $model::findOrFail($id) : new $model;
        $reglas = ['nombre' => ['required', 'string', 'max:255', Rule::unique($tipo, 'nombre')->ignore($id)]];
        if ($tipo === 'grupos') {
            $reglas['semestre'] = ['required', 'integer', 'between:1,255'];
        } else {
            $reglas['semestre'] = ['nullable', 'integer', 'between:1,255'];
            $reglas['clave'] = ['nullable', 'string', 'max:50', Rule::unique('materias', 'clave')->ignore($id)];
            $reglas['descripcion'] = ['nullable', 'string', 'max:5000'];
        }
        $registro->fill($request->validate($reglas))->save();

        if ($tipo === 'materias') {
            return redirect()->route('admin.materias.editar', $registro)->with('ok', 'Materia guardada.');
        }

        return back()->with('ok', 'Datos guardados.');
    }

    public function editarGrupo(Request $request, Grupo $grupo)
    {
        $filtros = $request->validate([
            'buscar_alumno' => ['nullable', 'string', 'max:255'],
            'origen' => ['nullable', Rule::in(['todos', 'inscritos', 'sin_grupo'])],
            'buscar_materia' => ['nullable', 'string', 'max:255'],
            'semestre' => ['nullable', 'integer', 'between:1,255'],
        ]);
        $alumnos = User::where('rol', 'alumno')->with('grupo')
            ->when($filtros['buscar_alumno'] ?? null, fn ($query, $texto) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$texto.'%')->orWhere('email', 'like', '%'.$texto.'%')->orWhere('matricula', 'like', '%'.$texto.'%')))
            ->when(($filtros['origen'] ?? '') === 'inscritos', fn ($query) => $query->where('grupo_id', $grupo->id))
            ->when(($filtros['origen'] ?? '') === 'sin_grupo', fn ($query) => $query->whereNull('grupo_id'))
            ->orderBy('name')->paginate(20, ['*'], 'alumnos_page')->withQueryString();
        $materias = Materia::query()
            ->when($filtros['buscar_materia'] ?? null, fn ($query, $texto) => $query->where('nombre', 'like', '%'.$texto.'%'))
            ->when($filtros['semestre'] ?? null, fn ($query, $semestre) => $query->where('semestre', $semestre))
            ->orderBy('semestre')->orderBy('nombre')->paginate(20, ['*'], 'materias_page')->withQueryString();

        return view('admin.editar-grupo', [
            'grupo' => $grupo, 'alumnos' => $alumnos, 'materias' => $materias,
            'materiasAsignadas' => $grupo->materias()->pluck('materias.id')->all(),
            'clases' => $grupo->clases()->with(['materia', 'docente'])->orderByDesc('fecha')->paginate(15, ['*'], 'clases_page')->withQueryString(),
            'inscritos' => $grupo->alumnos()->count(),
        ]);
    }

    public function alumnosGrupo(Request $request, Grupo $grupo)
    {
        $datos = $request->validate([
            'alumnos' => ['required', 'array', 'min:1', 'max:500'],
            'alumnos.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('rol', 'alumno')],
            'accion' => ['required', Rule::in(['agregar', 'quitar'])],
            'confirmar_traslado' => ['sometimes', 'accepted'],
        ]);

        return DB::transaction(function () use ($datos, $grupo, $request) {
            $usuarios = User::whereIn('id', $datos['alumnos'])->where('rol', 'alumno')->lockForUpdate()->get();
            if ($datos['accion'] === 'agregar') {
                $traslados = $usuarios->whereNotNull('grupo_id')->where('grupo_id', '!=', $grupo->id)->count();
                if ($traslados && ! $request->boolean('confirmar_traslado')) {
                    return back()->withErrors(['alumnos' => 'Seleccionaste alumnos de otro grupo. Marca la confirmación de traslado para moverlos.'])->withInput();
                }
                $cantidad = User::whereIn('id', $usuarios->pluck('id'))->update(['grupo_id' => $grupo->id]);
            } else {
                $cantidad = User::whereIn('id', $usuarios->pluck('id'))->where('grupo_id', $grupo->id)->update(['grupo_id' => null]);
            }

            return back()->with('ok', $cantidad.' alumnos procesados. El historial anterior se conserva.');
        });
    }

    public function materiasGrupo(Request $request, Grupo $grupo)
    {
        $datos = $request->validate([
            'materias' => ['required', 'array', 'min:1', 'max:500'],
            'materias.*' => ['required', 'integer', 'distinct', 'exists:materias,id'],
            'accion' => ['required', Rule::in(['agregar', 'quitar'])],
        ]);
        if ($datos['accion'] === 'agregar') {
            $grupo->materias()->syncWithoutDetaching($datos['materias']);
        } else {
            $grupo->materias()->detach($datos['materias']);
        }

        return back()->with('ok', 'Lista de materias actualizada. Las clases existentes se conservan; asignar una materia no programa su horario.');
    }

    public function crearMateriaGrupo(Request $request, Grupo $grupo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:materias,nombre'],
            'semestre' => ['nullable', 'integer', 'between:1,255'],
        ]);
        DB::transaction(function () use ($datos, $grupo) {
            $materia = Materia::create($datos);
            $grupo->materias()->attach($materia->id);
        });

        return back()->with('ok', 'Materia creada y agregada al grupo. Ya puedes programar sus clases.');
    }

    public function confirmarEliminarGrupo(Grupo $grupo)
    {
        return view('admin.eliminar-grupo', [
            'grupo' => $grupo,
            'clases' => $grupo->clases()->count(),
            'usuarios' => User::where('grupo_id', $grupo->id)->count(),
            'asistencias' => Asistencia::whereIn('clase_id', $grupo->clases()->select('id'))->count(),
            'excepciones' => Excepcion::where('grupo_id', $grupo->id)->count(),
        ]);
    }

    public function eliminar(Request $request, string $tipo, int $id)
    {
        $model = $this->modelo($tipo);

        return DB::transaction(function () use ($request, $model, $id, $tipo) {
            $registro = $model::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($request->boolean('forzar')) {
                abort_unless($tipo === 'grupos', 422);
                $request->validate([
                    'forzar' => ['required', 'accepted'],
                    'confirmacion' => ['required', 'string', Rule::in([$registro->nombre])],
                    'acepto_eliminacion' => ['required', 'accepted'],
                ], [
                    'confirmacion.in' => 'Escribe exactamente el nombre del grupo para confirmar.',
                    'acepto_eliminacion.accepted' => 'Confirma que aceptas eliminar las clases y su historial.',
                ]);
                $claseIds = $registro->clases()->lockForUpdate()->pluck('id');
                Asistencia::whereIn('clase_id', $claseIds)->delete();
                Clase::whereIn('id', $claseIds)->delete();
                Excepcion::where('grupo_id', $registro->id)->delete();
                User::where('grupo_id', $registro->id)->update(['grupo_id' => null]);
                $registro->delete();

                return redirect()->route('admin.index')->with('ok', 'Grupo eliminado junto con sus clases, asistencias y excepciones de grupo. Las cuentas se conservaron sin grupo asignado. Si usas el clasificador, reentrena el modelo con el historial restante.');
            }
            $enUso = $registro->clases()->exists();
            if ($tipo === 'grupos') {
                $enUso = $enUso || $registro->alumnos()->exists() || Excepcion::where('grupo_id', $id)->exists();
            }
            if ($enUso) {
                return back()->withErrors(['administracion' => $tipo === 'grupos'
                    ? 'El grupo tiene datos asociados. Usa "Eliminación forzada" para revisar y confirmar el borrado completo.'
                    : 'No se puede eliminar: la materia tiene clases asociadas. Reasigna o elimina primero esas clases.']);
            }
            $registro->delete();

            return back()->with('ok', 'Registro eliminado.');
        });
    }

    public function actualizarClase(Request $request, Clase $clase)
    {
        if ($clase->asistencias()->exists() || $clase->cerrada_at) {
            return back()->withErrors(['administracion' => 'No se puede modificar una clase cerrada o con asistencias.']);
        }
        $datos = $request->validate([
            'docente_id' => ['required', Rule::exists('users', 'id')->where('rol', 'docente')->whereNotNull('aprobado_at')],
            'grupo_id' => ['required', 'exists:grupos,id'],
            'materia_id' => ['required', 'exists:materias,id'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'tolerancia_min' => ['required', 'integer', 'between:0,60'],
        ]);
        $clase->fill($datos);
        $clase->token_qr = null;
        $clase->qr_expira_at = null;
        $clase->save();
        $clase->grupo->materias()->syncWithoutDetaching([$clase->materia_id]);

        return back()->with('ok', 'Clase actualizada.');
    }

    public function eliminarClase(Clase $clase)
    {
        if ($clase->asistencias()->exists()) {
            return back()->withErrors(['administracion' => 'La clase tiene historial de asistencia y no se puede eliminar.']);
        }
        $clase->delete();

        return back()->with('ok', 'Clase eliminada.');
    }

    private function modelo(string $tipo): string
    {
        abort_unless(in_array($tipo, ['grupos', 'materias']), 404);

        return $tipo === 'grupos' ? Grupo::class : Materia::class;
    }
}
