<?php

namespace App\Http\Controllers;

use App\Services\AsistenteEscolar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use RuntimeException;

class AsistenteController extends Controller
{
    public function responder(Request $request, AsistenteEscolar $asistente)
    {
        $datos = $request->validate([
            'pregunta' => ['required', 'string', 'max:600'],
            'historial' => ['sometimes', 'array', 'max:4'],
            'historial.*.role' => ['required', 'in:user,assistant'],
            'historial.*.content' => ['required', 'string', 'max:600'],
        ]);
        $historial = array_map(fn ($mensaje) => ['role' => $mensaje['role'], 'content' => $mensaje['content']], $datos['historial'] ?? []);

        return $this->ejecutar(fn () => $asistente->responder($request->user()->rol, $datos['pregunta'], $historial));
    }

    public function descripcion(Request $request, AsistenteEscolar $asistente)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'semestre' => ['nullable', 'integer', 'between:1,255'],
        ]);

        return $this->ejecutar(fn () => $asistente->descripcion($datos['nombre'], isset($datos['semestre']) ? (int) $datos['semestre'] : null));
    }

    private function ejecutar(callable $generar)
    {
        if (! config('asistente.enabled')) {
            return response()->json(['message' => 'El asistente está desactivado. Puedes seguir usando el sistema y escribir manualmente.'], 503);
        }
        try {
            return response()->json(['respuesta' => $generar()])->header('Cache-Control', 'no-store');
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            return response()->json(['message' => 'La IA local no está disponible o está ocupada. Comprueba que Ollama esté iniciado y vuelve a intentarlo. Puedes seguir usando el sistema y escribir manualmente.'], 503)->header('Cache-Control', 'no-store');
        }
    }
}
