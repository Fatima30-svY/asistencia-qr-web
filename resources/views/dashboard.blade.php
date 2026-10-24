@php
    $usuario = auth()->user();
    $hoy = now()->toDateString();
    $esDocente = $usuario->rol === 'docente';

    $alumnos = \App\Models\User::where('rol', 'alumno')
        ->when($esDocente, fn ($consulta) => $consulta->whereIn('grupo_id', \App\Models\Clase::where('docente_id', $usuario->id)->select('grupo_id')))->count();

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

            @if ($esDocente && ! $usuario->aprobado_at)
                <div class="rounded-lg bg-amber-50 p-6 text-amber-900">Tu cuenta docente está pendiente de aprobación. El director debe aprobarte y asignarte clases antes de que puedas gestionar asistencia.</div>
            @elseif (in_array($usuario->rol, ['admin', 'docente']))
                @if ($usuario->rol === 'admin')
                    <a href="{{ route('admin.index') }}" class="block rounded-lg border border-blue-200 bg-white p-5 font-semibold text-blue-700">Control escolar →</a>
                @endif
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
                    @if (in_array($usuario->rol, ['admin', 'docente']))
                        <a href="{{ route('docente.clases') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-600">
                            <p class="font-semibold text-slate-900">{{ $esDocente ? 'Mis clases' : 'Administrar clases' }}</p>
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
                    <a href="{{ route('alumno.index') }}" class="font-semibold text-blue-700 underline">Escanear QR y consultar mis asistencias, retardos y faltas</a>
                </div>
            @endif
            @if ($usuario->rol === 'admin' || ($esDocente && $usuario->aprobado_at))
                @php
                    $conteoGrafica = \App\Models\Asistencia::query()
                        ->when($esDocente, fn ($consulta) => $consulta->whereHas('clase', fn ($clases) => $clases->where('docente_id', $usuario->id)))
                        ->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');
                @endphp
                <x-asistencia-grafica :conteo="$conteoGrafica" />
            @endif
        </div>
    </div>
</x-app-layout>
