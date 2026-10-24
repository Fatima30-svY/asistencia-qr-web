<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.materias.index') }}" class="text-sm text-blue-700">← Materias</a>
        <h2 class="mt-3 text-3xl font-semibold">{{ $materia->exists ? 'Editar materia' : 'Nueva materia' }}</h2>
        <p class="mt-2 text-sm text-slate-500">Completa la ficha académica. La clave identifica la materia dentro de tu escuela.</p>
    </x-slot>
    <div class="mx-auto max-w-3xl p-6">
        @if (session('ok')) <p role="status" class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('ok') }}</p> @endif
        <form method="POST" action="{{ $materia->exists ? route('admin.editar', ['materias', $materia->id]) : route('admin.crear', 'materias') }}" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf @if ($materia->exists) @method('PUT') @endif
            @if ($errors->any()) <div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">@foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach</div> @endif
            @if ($materia->exists) <p class="text-xs text-slate-400">ID del registro: {{ $materia->id }}</p> @endif
            <label class="block text-sm font-medium">Nombre de la materia<x-text-input name="nombre" :value="old('nombre', $materia->nombre)" required maxlength="255" class="mt-2 block w-full" /></label>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="text-sm font-medium">Clave<x-text-input name="clave" :value="old('clave', $materia->clave)" maxlength="50" placeholder="Ej. PROG-701" class="mt-2 block w-full" /><span class="mt-2 block text-xs font-normal text-slate-500">Opcional y única. Usa la clave oficial si ya existe.</span></label>
                <label class="text-sm font-medium">Semestre<x-text-input type="number" name="semestre" :value="old('semestre', $materia->semestre)" min="1" max="255" class="mt-2 block w-full" /><span class="mt-2 block text-xs font-normal text-slate-500">Déjalo vacío si todavía no se ha definido.</span></label>
            </div>
            <label class="block text-sm font-medium">Descripción<textarea name="descripcion" rows="5" maxlength="5000" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Objetivo, contenido general o información de la materia">{{ old('descripcion', $materia->descripcion) }}</textarea></label>
            <div data-ai-description data-endpoint="{{ route('asistente.descripcion') }}" class="space-y-3">
                <button type="button" data-ai-suggest class="text-sm font-semibold text-blue-700 underline disabled:opacity-50">Sugerir descripción con IA</button>
                <p data-ai-description-status role="status" class="text-xs text-slate-500">Opcional. La propuesta no se aplica ni se guarda sin tu confirmación.</p>
                <div data-ai-preview hidden class="space-y-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-800">Borrador sugerido</p>
                    <p data-ai-description-text class="whitespace-pre-wrap text-sm text-slate-700"></p>
                    <div class="flex gap-4"><button type="button" data-ai-apply class="text-sm font-semibold text-blue-700">Usar sugerencia</button><button type="button" data-ai-discard class="text-sm text-slate-600">Descartar</button></div>
                </div>
            </div>
            <div class="flex items-center gap-4 border-t border-slate-100 pt-5"><x-primary-button>Guardar cambios</x-primary-button><a href="{{ route('admin.materias.index') }}" class="text-sm text-slate-500">Volver</a></div>
        </form>
    </div>
</x-app-layout>
