<details class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <summary class="w-fit cursor-pointer text-sm font-semibold text-blue-700">+ Nuevo grupo</summary>
    <form method="POST" action="{{ route('admin.crear', 'grupos') }}" class="mt-5 flex flex-wrap items-end gap-4">@csrf<label class="text-sm">Nombre<x-text-input name="nombre" required maxlength="255" class="mt-2 block" /></label><label class="text-sm">Semestre<x-text-input name="semestre" type="number" min="1" max="255" required class="mt-2 block w-24" /></label><x-primary-button>Crear grupo</x-primary-button></form>
</details>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Grupo</th><th class="px-5 py-4">Semestre</th><th class="px-5 py-4">Alumnos</th><th class="px-5 py-4">Clases</th><th class="px-5 py-4 text-right">Acciones</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($grupos as $grupo)
                <tr class="hover:bg-slate-50"><td class="px-5 py-5 font-semibold">{{ $grupo->nombre }}</td><td class="px-5 py-5">{{ $grupo->semestre }}</td><td class="px-5 py-5">{{ $grupo->alumnos_count }}</td><td class="px-5 py-5">{{ $grupo->clases_count }}</td><td class="px-5 py-5"><div class="flex justify-end gap-4"><a href="{{ route('admin.grupo.editar', $grupo) }}" class="font-semibold text-blue-700">Editar</a><details class="relative"><summary class="cursor-pointer text-slate-500">Eliminar</summary><div class="mt-3 space-y-3 rounded-lg border border-red-100 bg-white p-3"><form method="POST" action="{{ route('admin.eliminar', ['grupos', $grupo->id]) }}" onsubmit="return confirm('¿Eliminar este grupo sin datos asociados?')">@csrf @method('DELETE')<button class="whitespace-nowrap text-red-700">Eliminar grupo vacío</button></form><a href="{{ route('admin.grupo.confirmar-eliminacion', $grupo) }}" class="block whitespace-nowrap text-red-700 underline">Eliminación forzada</a></div></details></div></td></tr>
            @empty <tr><td colspan="5" class="p-10 text-center text-slate-500">No hay grupos. Crea el primero para organizar tus listas.</td></tr> @endforelse
        </tbody>
    </table>
</div>
