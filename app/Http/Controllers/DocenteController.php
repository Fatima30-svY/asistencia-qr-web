<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Materia;
use App\Services\ReglasAsistencia;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocenteController extends Controller
{
    public function clases()
    {
        $clases = Clase::with(['materia', 'grupo'])
            ->where('docente_id', auth()->id())
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
        ]);
    }

    public function guardar(Request $request)
    {
        $datos = $request->validate([
            'materia_id' => ['required', 'exists:materias,id'],
            'grupo_id' => ['required', 'exists:grupos,id'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'tolerancia_min' => ['required', 'integer', 'between:0,60'],
        ]);

        Clase::create($datos + ['docente_id' => auth()->id()]);

        return redirect()->route('docente.clases')->with('ok', 'Clase programada.');
    }

    public function qr(Clase $clase)
    {
        abort_unless($clase->docente_id === auth()->id(), 403);

        // Si el QR no existe o ya venció, se genera uno nuevo por 15 minutos
        if (! $clase->token_qr || ! $clase->qr_expira_at || now()->greaterThan($clase->qr_expira_at)) {
            $clase->update([
                'token_qr' => Str::random(32),
                'qr_expira_at' => now()->addMinutes(15),
            ]);
        }

        $payload = json_encode(['c' => $clase->id, 't' => $clase->token_qr]);

        $renderer = new ImageRenderer(new RendererStyle(280), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($payload);

        $clase->load(['materia', 'grupo', 'asistencias.alumno']);

        return view('docente.qr', compact('clase', 'svg'));
    }

    public function cerrar(Clase $clase, ReglasAsistencia $reglas)
    {
        abort_unless($clase->docente_id === auth()->id(), 403);

        $creados = $reglas->cerrarClase($clase);

        return redirect()
            ->route('docente.qr', $clase)
            ->with('ok', "Clase cerrada. Se registraron $creados faltas o justificaciones.");
    }
}