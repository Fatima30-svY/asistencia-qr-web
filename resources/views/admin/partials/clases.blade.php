<div class="flex items-center justify-between gap-3"><p class="text-sm text-slate-500">{{ $clases->count() }} clases programadas</p><a href="{{ route('docente.crear') }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Nueva clase</a></div>
@forelse ($clases as $clase)
    <details id="clase-{{ $clase->id }}" @if ((int) request('editar_clase') === $clase->id) open @endif class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <summary class="cursor-pointer"><span class="font-semibold">{{ $clase->materia->nombre }}</span><span class="mt-2 block text-sm text-slate-500">{{ $clase->grupo->nombre }} · {{ $clase->docente->name }} · {{ $clase->fecha->format('d/m/Y') }} · {{ substr($clase->hora_inicio, 0, 5) }}–{{ substr($clase->hora_fin, 0, 5) }}</span><span class="mt-3 block text-sm font-semibold text-blue-700">Editar</span></summary>
        <form method="POST" action="{{ route('admin.clase.editar', $clase) }}" class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
            @csrf @method('PUT')
            <label class="text-sm">Materia<select name="materia_id" class="mt-1 block w-full rounded-md border-slate-300">@foreach ($todasMaterias as $materia) <option value="{{ $materia->id }}" @selected($clase->materia_id === $materia->id)>{{ $materia->nombre }}</option>@endforeach</select></label>
            <label class="text-sm">Grupo<select name="grupo_id" class="mt-1 block w-full rounded-md border-slate-300">@foreach ($grupos as $grupo) <option value="{{ $grupo->id }}" @selected($clase->grupo_id === $grupo->id)>{{ $grupo->nombre }}</option>@endforeach</select></label>
            <label class="text-sm">Docente<select name="docente_id" class="mt-1 block w-full rounded-md border-slate-300"><option value="">Selecciona</option>@foreach ($docentes as $docente) <option value="{{ $docente->id }}" @selected($clase->docente_id === $docente->id)>{{ $docente->name }}</option>@endforeach</select></label>
            <label class="text-sm">Fecha<x-text-input name="fecha" type="date" :value="$clase->fecha->toDateString()" required class="mt-1 block w-full" /></label>
            <label class="text-sm">Inicio<x-text-input name="hora_inicio" type="time" :value="substr($clase->hora_inicio, 0, 5)" required class="mt-1 block w-full" /></label>
            <label class="text-sm">Fin<x-text-input name="hora_fin" type="time" :value="substr($clase->hora_fin, 0, 5)" required class="mt-1 block w-full" /></label>
            <label class="text-sm">Tolerancia<x-text-input name="tolerancia_min" type="number" :value="$clase->tolerancia_min" min="0" max="60" required class="mt-1 block w-full" /></label>
            <div class="self-end"><x-primary-button>Guardar cambios</x-primary-button></div>
        </form>
        <form method="POST" action="{{ route('admin.clase.eliminar', $clase) }}" class="mt-5" onsubmit="return confirm('¿Eliminar esta clase? Solo se puede eliminar si no tiene historial.')">@csrf @method('DELETE')<button class="text-sm text-red-700">Eliminar</button></form>
    </details>
@empty <p class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">No hay clases programadas.</p> @endforelse
