<div class="flex flex-wrap items-center justify-between gap-4"><p class="text-sm text-slate-500">{{ $materias->total() }} materias en el catálogo</p><a href="{{ route('admin.materias.crear') }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Nueva materia</a></div>
<form method="GET" action="{{ route('admin.materias.index') }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-white p-5">
    <label class="min-w-0 flex-1 text-sm font-medium">Buscar materia<x-text-input name="buscar_materia" :value="request('buscar_materia')" placeholder="Nombre o clave" class="mt-2 block w-full" /></label>
    <label class="text-sm font-medium">Semestre<x-text-input name="semestre_materia" type="number" min="1" max="255" :value="request('semestre_materia')" class="mt-2 block w-28" /></label>
    <x-primary-button>Filtrar</x-primary-button><a href="{{ route('admin.materias.index') }}" class="py-2 text-sm text-slate-500 underline">Limpiar</a>
</form>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Materia</th><th class="px-5 py-4">Clave</th><th class="px-5 py-4">Semestre</th><th class="px-5 py-4">Clases</th><th class="px-5 py-4 text-right">Acciones</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($materias as $materia)
                <tr class="hover:bg-slate-50"><td class="max-w-xs px-5 py-5"><p class="font-semibold">{{ $materia->nombre }}</p><p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($materia->descripcion ?? 'Sin descripción', 100) }}</p></td><td class="px-5 py-5 font-mono text-xs">{{ $materia->clave ?: 'Sin clave' }}</td><td class="px-5 py-5">{{ $materia->semestre ?? 'Sin clasificar' }}</td><td class="px-5 py-5">{{ $materia->clases_count }}</td><td class="px-5 py-5"><div class="flex justify-end gap-3"><a href="{{ route('admin.materias.editar', $materia) }}" class="font-semibold text-blue-700">Editar</a><form method="POST" action="{{ route('admin.eliminar', ['materias', $materia->id]) }}" onsubmit="return confirm('¿Eliminar esta materia?')">@csrf @method('DELETE')<button class="text-red-700">Eliminar</button></form></div></td></tr>
            @empty <tr><td colspan="5" class="p-10 text-center text-slate-500">No se encontraron materias. Puedes crear una o cambiar los filtros.</td></tr> @endforelse
        </tbody>
    </table>
</div>
{{ $materias->links() }}
