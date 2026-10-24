<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Services\ReglasAsistencia;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $registros = $request->user()->asistencias()->with(['clase.materia', 'clase.grupo'])->latest('id')->paginate(30);
        $conteo = $request->user()->asistencias()->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');

        return view('alumno.index', compact('registros', 'conteo'));
    }

    public function confirmar(Request $request, Clase $clase)
    {
        abort_unless($request->user()->grupo_id === $clase->grupo_id, 403, 'No perteneces al grupo de esta clase.');
        $token = $request->validate(['token' => ['required', 'string', 'max:255']])['token'];

        return view('alumno.confirmar', compact('clase', 'token'));
    }

    public function registrar(Request $request, ReglasAsistencia $reglas)
    {
        $datos = $request->validate([
            'codigo' => ['nullable', 'required_without:clase_id', 'string', 'regex:/^[0-9]+-[a-fA-F0-9]{10}$/'],
            'clase_id' => ['nullable', 'required_without:codigo', 'integer', 'exists:clases,id'],
            'token' => ['nullable', 'required_without:codigo', 'string', 'max:255'],
        ]);
        try {
            $registro = DB::transaction(function () use ($datos, $request, $reglas) {
                if (! empty($datos['codigo'])) {
                    $claseId = explode('-', $datos['codigo'], 2)[0];
                    $clase = Clase::whereKey($claseId)->lockForUpdate()->first();
                    if (! $clase || ! $clase->codigoManual() || ! hash_equals($clase->codigoManual(), strtoupper($datos['codigo']))) {
                        throw new DomainException('El código no es válido o ya fue renovado. Pide el código actual al docente.');
                    }
                    $token = $clase->token_qr;
                } else {
                    $clase = Clase::whereKey($datos['clase_id'])->lockForUpdate()->firstOrFail();
                    $token = $datos['token'];
                }

                return $reglas->registrarEscaneo($clase, $request->user(), $token);
            });
        } catch (DomainException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 422)
                : back()->withErrors(['escaneo' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'])) {
                throw $exception;
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'No se pudo registrar. Actualiza tu historial para comprobar si ya existe.'], 409)
                : back()->withErrors(['escaneo' => 'No se pudo registrar. Comprueba tu historial.']);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Registro guardado: '.$registro->estado, 'estado' => $registro->estado], 201)
            : redirect()->route('alumno.index')->with('ok', 'Registro guardado: '.$registro->estado);
    }
}
