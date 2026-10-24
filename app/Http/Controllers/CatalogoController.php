<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    public function guardarMateria(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('materia', [
            'nombre' => ['required', 'string', 'max:255', 'unique:materias,nombre'],
            'semestre' => ['nullable', 'integer', 'between:1,255'],
        ]);

        Materia::create($datos);

        return redirect()->route('docente.crear')->with('ok', 'Materia registrada. Ya puedes seleccionarla al programar la clase.');
    }

    public function guardarGrupo(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('grupo', [
            'nombre' => ['required', 'string', 'max:255', 'unique:grupos,nombre'],
            'semestre' => ['required', 'integer', 'between:1,255'],
        ]);

        Grupo::create($datos);

        return redirect()->route('docente.crear')->with('ok', 'Grupo registrado. Ya puedes seleccionarlo al programar la clase.');
    }
}
