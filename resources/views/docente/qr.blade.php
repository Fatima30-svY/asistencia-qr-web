<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('docente.clases') }}" class="text-sm text-blue-700 hover:underline">← Mis clases</a>
        <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $clase->materia->nombre }}</h2>
        <p class="text-sm text-slate-500">
            Grupo {{ $clase->grupo->nombre }} · {{ $clase->fecha->format('d/m/Y') }} ·
            {{ substr($clase->hora_inicio, 0, 5) }} - {{ substr($clase->hora_fin, 0, 5) }}
        </p>
    </x-slot>


    @php
        $conteo = $clase->asistencias->countBy('estado');
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('ok'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('ok') }}
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-5">
                <div class="rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm md:col-span-2">
                    <div data-qr-display data-url="{{ route('docente.qr', $clase) }}" data-finalizada="{{ $finalizada ? '1' : '0' }}">
                        @include('docente.partials.qr-codigo')
                    </div>
                    <p data-qr-status class="mt-3 text-xs text-slate-500" role="status"></p>

                    @if (! $clase->cerrada_at)
                    <form method="POST" action="{{ route('docente.cerrar', $clase) }}" class="mt-5"
                          onsubmit="return confirm('¿Cerrar la clase? A quien no escaneó se le pondrá falta.')">
                        @csrf
                        <button class="w-full rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                            Cerrar clase
                        </button>
                    </form>
                    @endif
                </div>

                <div class="space-y-6 md:col-span-3">
                    <x-asistencia-grafica :conteo="$conteo" />
                    <section class="rounded-lg border border-slate-200 bg-white p-5">
                        <h3 class="font-semibold">Alumnos inscritos en el grupo ({{ $clase->grupo->alumnos->count() }})</h3>
                        @forelse ($clase->grupo->alumnos as $alumno)
                            <p class="mt-2 text-sm">{{ $alumno->name }} · {{ ucfirst($clase->asistencias->firstWhere('alumno_id', $alumno->id)?->estado ?? 'sin registro') }}</p>
                        @empty
                            <p class="mt-2 text-sm text-slate-600">El director debe asignar alumnos a este grupo desde Administración escolar.</p>
                        @endforelse
                    </section>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-2xl font-semibold text-emerald-600">{{ $conteo->get('asistencia', 0) }}</p>
                            <p class="text-xs text-slate-500">Asistencias</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-2xl font-semibold text-amber-600">{{ $conteo->get('retardo', 0) }}</p>
                            <p class="text-xs text-slate-500">Retardos</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-2xl font-semibold text-rose-600">{{ $conteo->get('falta', 0) }}</p>
                            <p class="text-xs text-slate-500">Faltas</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-2xl font-semibold text-blue-700">{{ $conteo->get('justificada', 0) }}</p>
                            <p class="text-xs text-slate-500">Justificadas</p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-slate-900">Registros ({{ $clase->asistencias->count() }})</h3>
                        <ul class="mt-3 divide-y divide-slate-200 text-sm">
                            @forelse ($clase->asistencias as $a)
                                @php
                                    $color = match ($a->estado) {
                                        'asistencia' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'retardo' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'falta' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        default => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    };
                                @endphp
                                <li class="flex items-center justify-between py-3">
                                    <span class="text-slate-800">
                                        {{ $a->alumno->name }}
                                        <span class="text-slate-400">({{ $a->alumno->matricula }})</span>
                                    </span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $color }}">
                                        {{ ucfirst($a->estado) }}
                                    </span>
                                </li>
                            @empty
                                <li class="py-3 text-slate-500">Aún no hay registros.</li>
                            @endforelse
                        </ul>
                        <a href="{{ route('docente.qr', $clase) }}" class="mt-3 inline-block text-sm text-blue-700 underline">Actualizar lista y QR</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
