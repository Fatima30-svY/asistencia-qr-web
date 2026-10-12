<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-slate-900">Alumnos en riesgo de ausentismo</h2>
        <p class="mt-1 text-sm text-slate-500">
            Clasificador Naive Bayes: estima la probabilidad de falta en la próxima clase según las faltas y retardos recientes, el historial, el día y el horario.
        </p>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">

            @if (session('ok'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('ok') }}
                </div>
            @endif

            <div class="flex justify-end">
                <form method="POST" action="{{ route('riesgo.entrenar') }}">
                    @csrf
                    <button class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Reentrenar modelo
                    </button>
                </form>
            </div>

            @if (! $modeloListo)
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Aún no hay un modelo entrenado. Presiona "Reentrenar modelo".
                </div>
            @else
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Alumno</th>
                                    <th class="px-5 py-3 font-medium">Faltas recientes</th>
                                    <th class="px-5 py-3 font-medium">Retardos recientes</th>
                                    <th class="px-5 py-3 font-medium">Faltas históricas</th>
                                    <th class="px-5 py-3 font-medium">Prob. de falta</th>
                                    <th class="px-5 py-3 font-medium">Riesgo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($filas as $f)
                                    @php
                                        $porcentaje = round($f['probabilidad'] * 100);

                                        $badge = match ($f['riesgo']) {
                                            'alto' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                            'medio' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                            default => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        };

                                        $barra = match ($f['riesgo']) {
                                            'alto' => 'bg-rose-500',
                                            'medio' => 'bg-amber-500',
                                            default => 'bg-emerald-500',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-4 font-medium text-slate-900">
                                            {{ $f['alumno']->name }}

                                            <span class="font-normal text-slate-400">({{ $f['alumno']->matricula }})</span>
                                        </td>
                                        <td class="px-5 py-4 text-slate-600">{{ $f['x']['faltas'] }} de las últimas 10</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $f['x']['retardos'] }} de las últimas 10</td>
                                        <td class="px-5 py-4 text-slate-600">{{ ucfirst($f['x']['tasa'] ?? 'sin datos') }}</td>
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-2 rounded-full {{ $barra }}" style="width: {{ $porcentaje }}%"></div>
                                                </div>
                                                <span class="w-10 font-medium text-slate-800">{{ $porcentaje }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badge }}">
                                                {{ strtoupper($f['riesgo']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>