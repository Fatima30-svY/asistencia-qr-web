<form method="GET" action="{{ route('admin.'.$seccion.'.index') }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-white p-5">
    <label class="min-w-0 flex-1 text-sm font-medium">Buscar {{ $seccion }}<x-text-input name="buscar_usuario" :value="request('buscar_usuario')" placeholder="Nombre, correo o matrícula" class="mt-2 block w-full" /></label>
    @if ($seccion === 'alumnos')
        <label class="text-sm font-medium">Grupo<select name="grupo" class="mt-2 block rounded-md border-slate-300"><option value="">Todos los grupos</option>@foreach ($grupos as $grupo) <option value="{{ $grupo->id }}" @selected((int) request('grupo') === $grupo->id)>{{ $grupo->nombre }}</option>@endforeach</select></label>
    @endif
    <x-primary-button>Buscar</x-primary-button>
    <a href="{{ route('admin.'.$seccion.'.index') }}" class="py-2 text-sm text-slate-500 underline">Limpiar</a>
</form>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 text-sm text-slate-500">{{ $usuarios->total() }} {{ $seccion }} · 20 por página</div>
    @forelse ($usuarios as $usuario)
        <div class="border-b border-slate-100 p-5 last:border-0">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-50 font-semibold text-blue-700">{{ mb_substr($usuario->name, 0, 1) }}</span>
                    <div><p class="font-semibold text-slate-900">{{ $usuario->name }}</p><p class="mt-1 break-all text-sm text-slate-500">{{ $usuario->email }}</p>
                        @if ($seccion === 'alumnos') <p class="mt-2 text-sm text-slate-600">{{ $usuario->matricula ?: 'Sin matrícula' }} · {{ $usuario->grupo?->nombre ?? 'Sin grupo' }}</p>
                        @else <p class="mt-2 text-sm text-slate-600">Grupos: {{ $clases->where('docente_id', $usuario->id)->pluck('grupo.nombre')->unique()->join(', ') ?: 'Sin asignaciones' }}</p> @endif
                    </div>
                </div>
                @if ($seccion === 'docentes')
                    <form method="POST" action="{{ route('admin.usuario', $usuario) }}" class="flex flex-wrap items-center gap-3">
                        @csrf @method('PATCH')
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $usuario->aprobado_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $usuario->aprobado_at ? 'Aprobado' : 'Pendiente' }}</span>
                        <input type="hidden" name="aprobado" value="{{ $usuario->aprobado_at ? 0 : 1 }}">
                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium">{{ $usuario->aprobado_at ? 'Retirar aprobación' : 'Aprobar' }}</button>
                    </form>
                @endif
            </div>
            <details class="mt-4">
                <summary class="w-fit cursor-pointer text-sm font-semibold text-blue-700">Editar</summary>
                <form method="POST" action="{{ route('admin.usuario', $usuario) }}" class="mt-4 grid gap-4 rounded-lg bg-slate-50 p-4 sm:grid-cols-2">
                    @csrf @method('PATCH')
                    <label class="text-sm">Nombre<x-text-input name="name" :value="$usuario->name" required class="mt-1 block w-full" /></label>
                    <label class="text-sm">Correo<x-text-input name="email" type="email" :value="$usuario->email" required class="mt-1 block w-full" /></label>
                    @if ($seccion === 'alumnos')
                        <label class="text-sm">Matrícula<x-text-input name="matricula" :value="$usuario->matricula" class="mt-1 block w-full" /></label>
                        <label class="text-sm">Grupo<select name="grupo_id" class="mt-1 block w-full rounded-md border-slate-300"><option value="">Sin asignar</option>@foreach ($grupos as $grupo) <option value="{{ $grupo->id }}" @selected($usuario->grupo_id === $grupo->id)>{{ $grupo->nombre }}</option>@endforeach</select></label>
                    @endif
                    <div class="sm:col-span-2"><x-primary-button>Guardar cambios</x-primary-button></div>
                </form>
            </details>
        </div>
    @empty <p class="p-8 text-center text-sm text-slate-500">No se encontraron {{ $seccion }} con estos filtros.</p> @endforelse
</div>
{{ $usuarios->links() }}
