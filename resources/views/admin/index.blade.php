@php
    $titulos = ['resumen' => 'Control escolar', 'docentes' => 'Docentes', 'alumnos' => 'Alumnos', 'materias' => 'Materias', 'grupos' => 'Grupos', 'clases' => 'Clases'];
    $descripciones = ['resumen' => 'Organiza la escuela desde un apartado para cada tarea.', 'docentes' => 'Revisa cuentas, aprueba docentes y consulta sus asignaciones.', 'alumnos' => 'Consulta estudiantes y administra sus datos y grupo.', 'materias' => 'Organiza el catálogo por clave y semestre.', 'grupos' => 'Administra grupos y prepara sus listas y clases.', 'clases' => 'Consulta y ajusta docentes, materias y horarios.'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-blue-700">Administración escolar</p>
        <h2 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $titulos[$seccion] }}</h2>
        <p class="mt-2 text-sm text-slate-500">{{ $descripciones[$seccion] }}</p>
    </x-slot>
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('ok')) <p role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('ok') }}</p> @endif
        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">@foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach</div>
        @endif
        @if ($seccion === 'resumen')
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach (['docentes' => 'Aprobación y asignaciones', 'alumnos' => 'Estudiantes, matrículas y grupos', 'materias' => 'Claves, descripción y semestres', 'grupos' => 'Listas y organización de clases'] as $apartado => $detalle)
                    <a href="{{ route('admin.'.$apartado.'.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-400 hover:shadow-md">
                        <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ ucfirst($apartado) }}</h3><span class="rounded-lg bg-blue-50 px-3 py-1 text-xl font-semibold text-blue-700">{{ $totales[$apartado] }}</span></div>
                        <p class="mt-3 text-sm text-slate-500">{{ $detalle }}</p>
                        <p class="mt-5 text-sm font-medium text-blue-700">Abrir →</p>
                    </a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-6">
                <div><h3 class="font-semibold">{{ $totales['pendientes'] }} docentes pendientes</h3><p class="mt-1 text-sm text-slate-500">Aprueba sus cuentas antes de asignarles clases.</p></div>
                <a href="{{ route('admin.docentes.index') }}" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Revisar docentes</a>
            </div>
        @elseif (in_array($seccion, ['docentes', 'alumnos']))
            @include('admin.partials.usuarios')
        @elseif ($seccion === 'materias')
            @include('admin.partials.materias')
        @elseif ($seccion === 'grupos')
            @include('admin.partials.grupos')
        @elseif ($seccion === 'clases')
            @include('admin.partials.clases')
        @endif
    </div>
</x-app-layout>
