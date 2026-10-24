<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ auth()->user()->rol === 'admin' ? 'Clases' : 'Mis clases' }}</h2>
                <p class="mt-1 text-sm text-slate-500">Abre el QR de una clase en curso para que tu grupo registre su asistencia.</p>
            </div>
            @if (auth()->user()->rol === 'admin')
            <a href="{{ route('admin.index') }}" class="text-sm text-blue-700 underline">Administrar grupos, materias, docentes y alumnos</a>
            <a href="{{ route('docente.crear') }}"
               class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                Programar clase
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">

            @if (session('ok'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('ok') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Fecha</th>
                                <th class="px-5 py-3 font-medium">Materia</th>
                                <th class="px-5 py-3 font-medium">Grupo</th>
                                <th class="px-5 py-3 font-medium">Horario</th>
                                <th class="px-5 py-3 font-medium">Estado</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($clases->take(30) as $clase)
                                @php
                                    $ini = \Carbon\Carbon::parse($clase->fecha->toDateString().' '.$clase->hora_inicio);
                                    $fin = \Carbon\Carbon::parse($clase->fecha->toDateString().' '.$clase->hora_fin);
                                    if ($fin->lte($ini)) {
                                        $fin->addDay();
                                    }

                                    $estado = $clase->cerrada_at ? 'Cerrada' : (now()->lt($ini) ? 'Próxima' : (now()->lte($fin) ? 'En curso' : 'Terminada'));

                                    $badge = match ($estado) {
                                        'En curso' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'Próxima' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 text-slate-600">{{ $clase->fecha->format('d/m/Y') }}</td>
                                    <td class="px-5 py-4 font-medium text-slate-900">{{ $clase->materia->nombre }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $clase->grupo->nombre }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ substr($clase->hora_inicio, 0, 5) }} - {{ substr($clase->hora_fin, 0, 5) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge }}">{{ $estado }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if (in_array($estado, ['Terminada', 'Cerrada']))
                                            <a href="{{ route('docente.qr', $clase) }}"
                                               class="rounded-md border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
                                                Ver registro
                                            </a>
                                        @else
                                            <a href="{{ route('docente.qr', $clase) }}"
                                               class="rounded-md bg-blue-700 px-3 py-1.5 font-medium text-white hover:bg-blue-800">
                                                Abrir QR
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        {{ auth()->user()->rol === 'admin' ? 'No hay clases registradas. Usa "Programar clase" para crear una.' : 'Aún no tienes clases asignadas. Solicita al director que programe una clase para ti.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="text-xs text-slate-500">Se muestran las 30 clases más recientes.</p>
        </div>
    </div>
</x-app-layout>
