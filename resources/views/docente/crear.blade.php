<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('docente.clases') }}" class="text-sm text-blue-700 hover:underline">← Mis clases</a>
        <h2 class="mt-1 text-2xl font-semibold text-slate-900">Programar clase</h2>
        <p class="mt-1 text-sm text-slate-500">Crea la clase y después abre su QR cuando empiece.</p>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">

                @if ($materias->isEmpty() || $grupos->isEmpty())
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Aún no hay materias o grupos registrados, así que no se puede programar una clase.
                    </div>
                @else
                    <form method="POST" action="{{ route('docente.guardar') }}" class="grid gap-4 sm:grid-cols-2">
                        @csrf

                        <div>
                            <x-input-label for="materia_id" value="Materia" />
                            <select id="materia_id" name="materia_id" required
                                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="">Selecciona una materia</option>
                                @foreach ($materias as $materia)
                                    <option value="{{ $materia->id }}" @selected((int) old('materia_id') === $materia->id)>
                                        {{ $materia->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('materia_id')" class="mt-2" />
                        </div>


                        <div>
                            <x-input-label for="grupo_id" value="Grupo" />
                            <select id="grupo_id" name="grupo_id" required
                                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="">Selecciona un grupo</option>
                                @foreach ($grupos as $grupo)
                                    <option value="{{ $grupo->id }}" @selected((int) old('grupo_id') === $grupo->id)>
                                        Grupo {{ $grupo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('grupo_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <x-text-input id="fecha" class="mt-1 block w-full" type="date" name="fecha"
                                          :value="old('fecha', now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="tolerancia_min" value="Tolerancia para asistencia (minutos)" />
                            <x-text-input id="tolerancia_min" class="mt-1 block w-full" type="number" name="tolerancia_min"
                                          min="0" max="60" :value="old('tolerancia_min', 10)" required />
                            <x-input-error :messages="$errors->get('tolerancia_min')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="hora_inicio" value="Hora de inicio" />
                            <x-text-input id="hora_inicio" class="mt-1 block w-full" type="time" name="hora_inicio"

                                          :value="old('hora_inicio')" required />
                            <x-input-error :messages="$errors->get('hora_inicio')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="hora_fin" value="Hora de fin" />
                            <x-text-input id="hora_fin" class="mt-1 block w-full" type="time" name="hora_fin"
                                          :value="old('hora_fin')" required />
                            <x-input-error :messages="$errors->get('hora_fin')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-3 sm:col-span-2">
                            <x-primary-button>Programar clase</x-primary-button>
                            <a href="{{ route('docente.clases') }}"
                               class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Cancelar
                            </a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>