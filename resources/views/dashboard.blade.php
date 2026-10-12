@php
    $usuario = auth()->user();
    $hoy = now()->toDateString();
    $esDocente = $usuario->rol === 'docente';

    $alumnos = \App\Models\User::where('rol', 'alumno')->count();

    $clasesHoy = \App\Models\Clase::whereDate('fecha', $hoy)
        ->when($esDocente, fn ($q) => $q->where('docente_id', $usuario->id))
        ->count();

    $registrosHoy = \App\Models\Asistencia::whereHas('clase', function ($q) use ($hoy, $esDocente, $usuario) {
        $q->whereDate('fecha', $hoy)
          ->when($esDocente, fn ($q2) => $q2->where('docente_id', $usuario->id));
    })->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-slate-900">Inicio</h2>
        <p class="mt-1 text-sm text-slate-500">Bienvenido, {{ $usuario->name }}.</p>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (in_array($usuario->rol, ['admin', 'docente']))
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Alumnos registrados</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $alumnos }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Clases de hoy</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $clasesHoy }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Registros de hoy</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $registrosHoy }}</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @if ($esDocente)
                        <a href="{{ route('docente.clases') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-600">
                            <p class="font-semibold text-slate-900">Mis clases</p>
                            <p class="mt-1 text-sm text-slate-500">Abre el código QR de una clase y consulta sus registros.</p>
                        </a>
                    @endif

                    <a href="{{ route('riesgo.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-600">
                        <p class="font-semibold text-slate-900">Alumnos en riesgo</p>
                        <p class="mt-1 text-sm text-slate-500">Probabilidad de ausentismo calculada con el clasificador bayesiano.</p>
                    </a>
                </div>
            @else
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-slate-600">El registro de asistencia para alumnos estará disponible pronto.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>