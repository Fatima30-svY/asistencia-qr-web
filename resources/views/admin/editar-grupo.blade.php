<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.grupos.index') }}" class="text-sm text-blue-700 underline">← Grupos</a>
        <h2 class="mt-2 text-2xl font-semibold">Editar grupo: {{ $grupo->nombre }}</h2>
        <p class="mt-2 text-sm">Semestre {{ $grupo->semestre }} · {{ $inscritos }} alumnos inscritos</p>
    </x-slot>
    <div class="mx-auto max-w-6xl space-y-6 p-6">
        @if (session('ok')) <p role="status" class="rounded bg-emerald-50 p-4 text-emerald-800">{{ session('ok') }}</p> @endif
        @if ($errors->any()) <div role="alert" class="rounded bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach</div> @endif

        <form method="POST" action="{{ route('admin.editar', ['grupos', $grupo->id]) }}" class="flex flex-wrap items-end gap-4 rounded-lg bg-white p-5">
            @csrf @method('PUT')
            <label>Nombre <x-text-input name="nombre" :value="$grupo->nombre" required class="block" /></label>
            <label>Semestre <x-text-input name="semestre" :value="$grupo->semestre" type="number" min="1" max="255" required class="block w-24" /></label>
            <x-primary-button>Guardar grupo</x-primary-button>
        </form>

        <section id="alumnos" class="space-y-4 rounded-lg bg-white p-5">
            <h3 class="text-lg font-semibold">Lista de estudiantes</h3>
            <form method="GET" action="{{ route('admin.grupo.editar', $grupo) }}#alumnos" class="flex flex-wrap items-end gap-3">
                <label>Buscar estudiante <x-text-input name="buscar_alumno" :value="request('buscar_alumno')" placeholder="Nombre, matrícula o correo" class="block" /></label>
                <label>Mostrar <select name="origen" class="block rounded border-slate-300"><option value="todos">Todos</option><option value="inscritos" @selected(request('origen') === 'inscritos')>Inscritos en este grupo</option><option value="sin_grupo" @selected(request('origen') === 'sin_grupo')>Sin grupo</option></select></label>
                <x-primary-button>Buscar estudiantes</x-primary-button>
                <a href="{{ route('admin.grupo.editar', $grupo) }}#alumnos" class="text-blue-700 underline">Limpiar</a>
            </form>
            <p class="text-sm">{{ $alumnos->total() }} resultados · 20 por página. La selección se aplica solo a esta página; guarda antes de cambiar de página o filtro.</p>
            <form method="POST" action="{{ route('admin.grupo.alumnos', $grupo) }}" x-data="{ seleccion: @js(array_map('strval', old('alumnos', []))) }" class="space-y-3">
                @csrf @method('PATCH')
                <div class="flex flex-wrap gap-3 text-sm">
                    <button type="button" class="text-blue-700 underline" @click="seleccion = Array.from($el.closest('form').querySelectorAll('[data-alumno]')).map(casilla => casilla.value)">Seleccionar esta página</button>
                    <button type="button" class="text-blue-700 underline" @click="seleccion = []">Quitar selección</button>
                    <span x-text="seleccion.length + ' seleccionados'"></span>
                </div>
                <div class="max-h-96 overflow-y-auto divide-y rounded border border-slate-200">
                    @forelse ($alumnos as $alumno)
                        <label class="flex items-start gap-3 p-3">
                            <input data-alumno type="checkbox" name="alumnos[]" value="{{ $alumno->id }}" x-model="seleccion" class="mt-1 rounded border-slate-300">
                            <span><strong>{{ $alumno->name }}</strong><br><span class="text-sm text-slate-600">{{ $alumno->matricula ?: 'Sin matrícula' }} · {{ $alumno->email }} · {{ $alumno->grupo?->nombre ?? 'Sin grupo' }}</span></span>
                        </label>
                    @empty <p class="p-4">No hay estudiantes con estos filtros.</p> @endforelse
                </div>
                <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="confirmar_traslado" value="1" class="mt-1 rounded border-slate-300"><span>Confirmo mover a este grupo los alumnos seleccionados que ya pertenecen a otro. Su historial anterior se conserva.</span></label>
                <div class="flex flex-wrap gap-3">
                    <x-primary-button name="accion" value="agregar">Agregar seleccionados al grupo</x-primary-button>
                    <button name="accion" value="quitar" class="rounded border border-red-300 px-4 py-2 text-red-700" onclick="return confirm('¿Quitar del grupo a los alumnos seleccionados que están inscritos aquí? Sus cuentas e historial se conservan.')">Quitar seleccionados del grupo</button>
                </div>
            </form>
            {{ $alumnos->fragment('alumnos')->links() }}
        </section>

        <section id="materias" class="space-y-4 rounded-lg bg-white p-5">
            <h3 class="text-lg font-semibold">Materias del grupo</h3>
            <p class="text-sm text-slate-600">Organiza el plan del grupo. Para impartir una materia debes crear también su clase con docente y horario.</p>
            <form method="POST" action="{{ route('admin.grupo.materia.crear', $grupo) }}" class="flex flex-wrap items-end gap-3 rounded border border-slate-200 p-4">
                @csrf
                <label>Nueva materia <x-text-input name="nombre" required maxlength="255" class="block" /></label>
                <label>Semestre <x-text-input type="number" name="semestre" min="1" max="255" :value="$grupo->semestre" class="block w-24" /></label>
                <x-primary-button>Crear y agregar al grupo</x-primary-button>
            </form>
            <form method="GET" action="{{ route('admin.grupo.editar', $grupo) }}#materias" class="flex flex-wrap items-end gap-3">
                <label>Buscar materia <x-text-input name="buscar_materia" :value="request('buscar_materia')" placeholder="Nombre de la materia" class="block" /></label>
                <label>Semestre <x-text-input type="number" name="semestre" min="1" max="255" :value="request('semestre')" class="block w-24" /></label>
                <x-primary-button>Filtrar materias</x-primary-button>
                <a href="{{ route('admin.grupo.editar', $grupo) }}#materias" class="text-blue-700 underline">Ver todas</a>
            </form>
            <p class="text-sm">{{ $materias->total() }} resultados · guarda la selección antes de cambiar de página.</p>
            <form method="POST" action="{{ route('admin.grupo.materias', $grupo) }}" x-data="{ seleccion: [] }" class="space-y-3">
                @csrf @method('PATCH')
                <div class="flex flex-wrap gap-3 text-sm">
                    <button type="button" class="text-blue-700 underline" @click="seleccion = Array.from($el.closest('form').querySelectorAll('[data-materia]')).map(casilla => casilla.value)">Seleccionar esta página</button>
                    <button type="button" class="text-blue-700 underline" @click="seleccion = []">Quitar selección</button>
                    <span x-text="seleccion.length + ' seleccionadas'"></span>
                </div>
                <div class="max-h-96 overflow-y-auto divide-y rounded border border-slate-200">
                    @forelse ($materias as $materia)
                        <label class="flex items-start gap-3 p-3">
                            <input data-materia type="checkbox" name="materias[]" value="{{ $materia->id }}" x-model="seleccion" class="mt-1 rounded border-slate-300">
                            <span>{{ $materia->nombre }} · {{ $materia->semestre ? 'Semestre '.$materia->semestre : 'Sin clasificar' }} · {{ in_array($materia->id, $materiasAsignadas) ? 'Asignada al grupo' : 'No asignada' }}</span>
                        </label>
                    @empty <p class="p-4">No hay materias con estos filtros. Puedes crearlas en Administración escolar.</p> @endforelse
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-primary-button name="accion" value="agregar">Agregar materias seleccionadas</x-primary-button>
                    <button name="accion" value="quitar" class="rounded border border-red-300 px-4 py-2 text-red-700">Quitar materias seleccionadas</button>
                </div>
            </form>
            {{ $materias->fragment('materias')->links() }}
        </section>

        <section class="space-y-4 rounded-lg bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-semibold">Clases del grupo ({{ $clases->total() }})</h3><a href="{{ route('docente.crear', ['grupo_id' => $grupo->id]) }}" class="font-semibold text-blue-700 underline">Agregar clase a este grupo</a></div>
            @forelse ($clases as $clase)
                <div class="border-t py-3"><p>{{ $clase->materia->nombre }} · {{ $clase->docente->name }} · {{ $clase->fecha->format('d/m/Y') }} · {{ substr($clase->hora_inicio, 0, 5) }}–{{ substr($clase->hora_fin, 0, 5) }}</p><a href="{{ route('admin.clases.index', ['editar_clase' => $clase->id]) }}#clase-{{ $clase->id }}" class="text-sm text-blue-700 underline">Editar</a></div>
            @empty <p>No hay clases programadas para este grupo.</p> @endforelse
            {{ $clases->links() }}
        </section>
    </div>
</x-app-layout>
