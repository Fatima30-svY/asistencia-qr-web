<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold">Confirmar eliminación forzada</h2>
        <p class="mt-2 text-slate-600">Grupo: {{ $grupo->nombre }}</p>
    </x-slot>
    <div class="mx-auto max-w-3xl space-y-5 p-6">
        <div class="rounded-lg border border-red-300 bg-white p-6">
            <h3 class="font-semibold text-red-800">Esta acción es permanente</h3>
            <p class="mt-3">Se eliminará el grupo <strong>{{ $grupo->nombre }}</strong> y los siguientes datos asociados:</p>
            <ul class="mt-3 list-inside list-disc space-y-2">
                <li>{{ $clases }} clases del grupo, incluso si están cerradas.</li>
                <li>{{ $asistencias }} registros de asistencia, retardos, faltas o justificaciones de esas clases.</li>
                <li>{{ $excepciones }} excepciones asignadas directamente al grupo.</li>
                <li>{{ $usuarios }} cuentas quedarán sin grupo. Sus cuentas y matrículas se conservarán.</li>
            </ul>
            <p class="mt-3">Las materias, docentes y demás grupos se conservan. Las gráficas dejarán de incluir el historial eliminado. Reentrena el clasificador después del borrado si lo utilizas.</p>
            <p class="mt-3 text-sm">Los totales se calculan al abrir esta pantalla; se eliminarán también los datos asociados que se agreguen antes de confirmar.</p>
        </div>
        @if ($errors->any())
            <div role="alert" class="rounded bg-red-50 p-4 text-red-800">
                @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('admin.eliminar', ['grupos', $grupo->id]) }}" class="space-y-4 rounded-lg bg-white p-6">
            @csrf @method('DELETE')
            <input type="hidden" name="forzar" value="1">
            <label for="confirmacion" class="block font-medium">Escribe el nombre exacto del grupo: {{ $grupo->nombre }}</label>
            <x-text-input id="confirmacion" name="confirmacion" :value="old('confirmacion')" required autocomplete="off" class="block w-full" />
            <label class="flex items-start gap-3">
                <input type="checkbox" name="acepto_eliminacion" value="1" required class="mt-1 rounded border-slate-300">
                <span>Confirmo que quiero eliminar el grupo, sus clases y el historial indicado, y dejar sus cuentas sin grupo.</span>
            </label>
            <div class="flex flex-wrap gap-4">
                <button class="rounded bg-red-700 px-4 py-2 font-semibold text-white">Eliminar grupo y datos asociados</button>
                <a href="{{ route('admin.index') }}" class="px-4 py-2 text-blue-700 underline">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
