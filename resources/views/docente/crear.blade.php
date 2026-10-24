<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('docente.clases') }}" class="text-sm text-blue-700 hover:underline">← Clases</a>
        <h2 class="mt-1 text-2xl font-semibold text-slate-900">Crear clase</h2>
        <p class="mt-1 text-sm text-slate-500">Crea o elige materia y grupo, asigna un docente y guarda todo junto.</p>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if (session('ok'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('ok') }}</div>
            @endif
            <form method="POST" action="{{ route('docente.guardar') }}" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <p class="font-semibold">La clase aún no se guardó. Revisa estos datos:</p>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <x-input-label for="docente_id" value="Docente responsable" />
                    <select id="docente_id" name="docente_id" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Selecciona un docente</option>
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}" @selected((int) old('docente_id', $docentes->count() === 1 ? $docentes->first()->id : null) === $docente->id)>{{ $docente->name }}</option>
                        @endforeach
                    </select>
                    @if ($docentes->isEmpty())
                        <p class="mt-2 text-sm text-amber-800">Se necesita un docente aprobado. <a class="underline" href="{{ route('admin.docentes.index') }}">Abrir Docentes</a>.</p>
                    @endif
                    <x-input-error :messages="$errors->get('docente_id')" class="mt-2" />
                </div>

                <fieldset class="space-y-3 rounded-md border border-slate-200 p-4">
                    <legend class="px-2 font-semibold text-slate-900">Materia</legend>
                    <p class="text-sm text-slate-600">Selecciona una existente o deja la selección en “Crear nueva materia” y escribe su nombre.</p>
                    <div>
                        <x-input-label for="materia_id" value="Materia existente" />
                        <select id="materia_id" name="materia_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Crear nueva materia</option>
                            @foreach ($materias as $materia)
                                <option value="{{ $materia->id }}" @selected((int) old('materia_id', request('materia_id')) === $materia->id)>{{ $materia->nombre }} · {{ $materia->semestre ? 'Semestre '.$materia->semestre : 'Sin clasificar' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="materia_nombre" value="Nombre de la nueva materia" />
                        <x-text-input id="materia_nombre" class="mt-1 block w-full" name="materia_nombre" :value="old('materia_nombre')" maxlength="255" placeholder="Ejemplo: Programación" />
                        <p class="mt-1 text-xs text-slate-500">Déjalo vacío si elegiste una materia existente.</p>
                    </div>
                    <label>Semestre de la nueva materia (opcional)
                        <x-text-input name="materia_semestre" type="number" min="1" max="255" :value="old('materia_semestre')" class="mt-1 block w-full" />
                    </label>
                </fieldset>

                <fieldset class="space-y-3 rounded-md border border-slate-200 p-4">
                    <legend class="px-2 font-semibold text-slate-900">Grupo</legend>
                    <p class="text-sm text-slate-600">Selecciona un grupo existente o escribe el nombre y semestre de uno nuevo.</p>
                    <div>
                        <x-input-label for="grupo_id" value="Grupo existente" />
                        <select id="grupo_id" name="grupo_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Crear nuevo grupo</option>
                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->id }}" @selected((int) old('grupo_id', request('grupo_id')) === $grupo->id)>{{ $grupo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="grupo_nombre" value="Nombre del nuevo grupo" />
                            <x-text-input id="grupo_nombre" class="mt-1 block w-full" name="grupo_nombre" :value="old('grupo_nombre')" maxlength="255" placeholder="Ejemplo: Grupo 704" />
                        </div>
                        <div>
                            <x-input-label for="semestre" value="Semestre del nuevo grupo" />
                            <x-text-input id="semestre" class="mt-1 block w-full" type="number" name="semestre" :value="old('semestre')" min="1" max="255" />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Deja los campos del nuevo grupo vacíos si elegiste uno existente.</p>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="fecha" value="Fecha de la clase" />
                        <x-text-input id="fecha" class="mt-1 block w-full" type="date" name="fecha" :value="old('fecha', now()->toDateString())" required />
                    </div>
                    <div>
                        <x-input-label for="tolerancia_min" value="Tolerancia (minutos)" />
                        <x-text-input id="tolerancia_min" class="mt-1 block w-full" type="number" name="tolerancia_min" :value="old('tolerancia_min', 10)" min="0" max="60" required />
                    </div>
                    <div>
                        <x-input-label for="hora_inicio" value="Hora de inicio" />
                        <x-text-input id="hora_inicio" class="mt-1 block w-full" type="time" name="hora_inicio" :value="old('hora_inicio')" required />
                    </div>
                    <div>
                        <x-input-label for="hora_fin" value="Hora de fin" />
                        <x-text-input id="hora_fin" class="mt-1 block w-full" type="time" name="hora_fin" :value="old('hora_fin')" required />
                    </div>
                </div>

                <p class="text-sm text-slate-600">Al guardar se crearán los datos nuevos y la clase aparecerá en el listado del director y del docente asignado.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button :disabled="$docentes->isEmpty()">Programar clase</x-primary-button>
                    <a href="{{ route('docente.clases') }}" class="text-sm text-blue-700 hover:underline">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
