<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use App\Services\ReglasAsistencia;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocenteController extends Controller
{
    public function clases()
    {
        $clases = Clase::with(['materia', 'grupo'])
            ->when(auth()->user()->rol !== 'admin', fn ($query) => $query->where('docente_id', auth()->id()))
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->get();

        return view('docente.clases', compact('clases'));
    }

    public function crear()
    {
        return view('docente.crear', [
            'materias' => Materia::orderBy('nombre')->get(),
            'grupos' => Grupo::orderBy('nombre')->get(),
            'docentes' => User::where('rol', 'docente')->whereNotNull('aprobado_at')->orderBy('name')->get(),
        ]);
    }

    public function guardar(Request $request)
    {
        $datos = $request->validate([
            'docente_id' => ['required', 'integer', Rule::exists('users', 'id')->where('rol', 'docente')->whereNotNull('aprobado_at')],
            'materia_id' => ['nullable', 'required_without:materia_nombre', Rule::prohibitedIf($request->filled('materia_nombre')), 'integer', 'exists:materias,id'],
            'materia_nombre' => ['nullable', 'required_without:materia_id', Rule::prohibitedIf($request->filled('materia_id')), 'string', 'max:255', 'unique:materias,nombre'],
            'materia_semestre' => ['nullable', 'integer', 'between:1,255'],
            'grupo_id' => ['nullable', 'required_without:grupo_nombre', Rule::prohibitedIf($request->filled('grupo_nombre')), 'integer', 'exists:grupos,id'],
            'grupo_nombre' => ['nullable', 'required_without:grupo_id', Rule::prohibitedIf($request->filled('grupo_id')), 'string', 'max:255', 'unique:grupos,nombre'],
            'semestre' => [Rule::excludeIf($request->filled('grupo_id')), 'required_with:grupo_nombre', 'nullable', 'integer', 'between:1,255'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'tolerancia_min' => ['required', 'integer', 'between:0,60'],
        ], [
            'materia_id.required_without' => 'Selecciona una materia existente o escribe el nombre de una nueva.',
            'materia_nombre.required_without' => 'Escribe el nombre de la nueva materia o selecciona una existente.',
            'grupo_id.required_without' => 'Selecciona un grupo existente o escribe el nombre de uno nuevo.',
            'grupo_nombre.required_without' => 'Escribe el nombre del nuevo grupo o selecciona uno existente.',
            'materia_id.prohibited' => 'Elige una materia existente o crea una nueva, solo una de las dos opciones.',
            'materia_nombre.prohibited' => 'Deja vacío el nombre nuevo si seleccionaste una materia existente.',
            'grupo_id.prohibited' => 'Elige un grupo existente o crea uno nuevo, solo una de las dos opciones.',
            'grupo_nombre.prohibited' => 'Deja vacío el nombre nuevo si seleccionaste un grupo existente.',
            'semestre.required_with' => 'Indica el semestre del nuevo grupo.',
        ]);

        DB::transaction(function () use ($datos) {
            $materiaId = $datos['materia_id'] ?? Materia::create(['nombre' => $datos['materia_nombre'], 'semestre' => $datos['materia_semestre'] ?? null])->id;
            $grupoId = $datos['grupo_id'] ?? Grupo::create([
                'nombre' => $datos['grupo_nombre'],
                'semestre' => $datos['semestre'],
            ])->id;

            Clase::create([
                'docente_id' => $datos['docente_id'],
                'materia_id' => $materiaId,
                'grupo_id' => $grupoId,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'tolerancia_min' => $datos['tolerancia_min'],
            ]);
            Grupo::findOrFail($grupoId)->materias()->syncWithoutDetaching([$materiaId]);
        });

        return redirect()->route('docente.clases')->with('ok', 'Clase programada.');
    }

    public function qr(Request $request, Clase $clase)
    {
        abort_unless(auth()->user()->rol === 'admin' || $clase->docente_id === auth()->id(), 403);
        $clase = DB::transaction(function () use ($clase) {
            $clase = Clase::whereKey($clase->id)->lockForUpdate()->firstOrFail();
            abort_unless(auth()->user()->rol === 'admin' || $clase->docente_id === auth()->id(), 403);
            $fin = Carbon::parse($clase->fecha->toDateString().' '.$clase->hora_fin);
            if (! $clase->cerrada_at && now()->lte($fin) && (! $clase->token_qr || ! $clase->qr_expira_at || now()->greaterThan($clase->qr_expira_at))) {
                $clase->update([
                    'token_qr' => Str::random(32),
                    'qr_expira_at' => now()->addMinutes(15)->min($fin),
                ]);
            }

            return $clase;
        });
        $finalizada = (bool) $clase->cerrada_at || now()->gt(Carbon::parse($clase->fecha->toDateString().' '.$clase->hora_fin));
        $svg = null;
        if (! $finalizada && $clase->token_qr) {
            $payload = route('alumno.confirmar', ['clase' => $clase->id, 'token' => $clase->token_qr]);
            $renderer = new ImageRenderer(new RendererStyle(280), new SvgImageBackEnd);
            $svg = (new Writer($renderer))->writeString($payload);
        }
        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('docente.partials.qr-codigo', compact('clase', 'svg', 'finalizada'))->render(),
                'finalizada' => $finalizada,
            ])->header('Cache-Control', 'no-store');
        }

        $clase->load(['materia', 'grupo.alumnos', 'asistencias.alumno']);

        return response()->view('docente.qr', compact('clase', 'svg', 'finalizada'))->header('Cache-Control', 'no-store');
    }

    public function cerrar(Clase $clase, ReglasAsistencia $reglas)
    {
        abort_unless(auth()->user()->rol === 'admin' || $clase->docente_id === auth()->id(), 403);

        $creados = $reglas->cerrarClase($clase);

        return redirect()
            ->route('docente.qr', $clase)
            ->with('ok', "Clase cerrada. Se registraron $creados faltas o justificaciones.");
    }
}
