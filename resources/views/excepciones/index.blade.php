@php
    $esAdmin = auth()->user()->rol === 'admin';

    $nombres = [
        'justificante' => 'Justificante',
        'permiso' => 'Permiso',
        'suspension' => 'Suspensión',
        'festivo' => 'Día festivo',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-slate-900">Excepciones</h2>
        <p class="mt-1 text-sm text-slate-500">
            Casos en los que la falta no se aplica. Se toman en cuenta al cerrar una clase.
        </p>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('ok'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('ok') }}
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Registrar excepción</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Regístrala <strong>antes de cerrar la clase</strong>: no cambia las clases que ya se cerraron.

                </p>

                <form method="POST" action="{{ route('excepciones.store') }}"
                      x-data="{ tipo: '{{ old('tipo', 'justificante') }}' }"
                      class="mt-5 grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <x-input-label for="tipo" value="Tipo" />
                        <select id="tipo" name="tipo" x-model="tipo"
                                class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @foreach ($tipos as $t)
                                <option value="{{ $t }}">{{ $nombres[$t] }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                    </div>

                    <div x-show="['justificante', 'permiso'].includes(tipo)" x-cloak>
                        <x-input-label for="alumno_id" value="Alumno" />
                        <select id="alumno_id" name="alumno_id"
                                class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="">Selecciona un alumno</option>
                            @foreach ($alumnos as $alumno)
                                <option value="{{ $alumno->id }}" @selected((int) old('alumno_id') === $alumno->id)>
                                    {{ $alumno->name }} ({{ $alumno->matricula }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('alumno_id')" class="mt-2" />
                    </div>


                    @if ($esAdmin)
                        <div x-show="['suspension', 'festivo'].includes(tipo)" x-cloak>
                            <x-input-label for="grupo_id" value="Aplica a" />
                            <select id="grupo_id" name="grupo_id"
                                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="">Toda la escuela</option>
                                @foreach ($grupos as $grupo)
                                    <option value="{{ $grupo->id }}" @selected((int) old('grupo_id') === $grupo->id)>
                                        Grupo {{ $grupo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('grupo_id')" class="mt-2" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="fecha_inicio" value="Desde" />
                        <x-text-input id="fecha_inicio" class="mt-1 block w-full" type="date" name="fecha_inicio"
                                      :value="old('fecha_inicio', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="fecha_fin" value="Hasta" />
                        <x-text-input id="fecha_fin" class="mt-1 block w-full" type="date" name="fecha_fin"
                                      :value="old('fecha_fin', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="motivo" value="Motivo" />

                        <x-text-input id="motivo" class="mt-1 block w-full" type="text" name="motivo"
                                      :value="old('motivo')" maxlength="255" required placeholder="Ej. Cita médica" />
                        <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>Registrar excepción</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Tipo</th>
                                <th class="px-5 py-3 font-medium">Aplica a</th>
                                <th class="px-5 py-3 font-medium">Fechas</th>
                                <th class="px-5 py-3 font-medium">Motivo</th>
                                <th class="px-5 py-3 font-medium">Registró</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($excepciones as $e)
                                @php
                                    $badge = match ($e->tipo) {
                                        'justificante' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'permiso' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                        'suspension' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        default => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',

                                    };

                                    $aplica = $e->alumno
                                        ? $e->alumno->name.' ('.$e->alumno->matricula.')'
                                        : ($e->grupo ? 'Grupo '.$e->grupo->nombre : 'Toda la escuela');

                                    $fechas = $e->fecha_inicio->equalTo($e->fecha_fin)
                                        ? $e->fecha_inicio->format('d/m/Y')
                                        : $e->fecha_inicio->format('d/m/Y').' - '.$e->fecha_fin->format('d/m/Y');

                                    $puedeBorrar = $esAdmin || $e->registrada_por === auth()->id();
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                            {{ $nombres[$e->tipo] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-800">{{ $aplica }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $fechas }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $e->motivo ?? '—' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $e->registradaPor->name ?? 'Sistema' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        @if ($puedeBorrar)
                                            <form method="POST" action="{{ route('excepciones.destroy', $e) }}"
                                                  onsubmit="return confirm('¿Eliminar esta excepción?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
                                                    Eliminar
                                                </button>
                                            </form>

                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">No hay excepciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>