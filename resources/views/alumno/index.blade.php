<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold">Mi asistencia</h2></x-slot>
    <div class="mx-auto max-w-4xl space-y-6 p-6">
        @if (session('ok')) <p role="status" class="text-emerald-700">{{ session('ok') }}</p> @endif
        <p>Grupo: {{ auth()->user()->grupo?->nombre ?? 'Sin asignar. Solicita tu asignación al director.' }}</p>
        <section class="space-y-3 rounded-lg bg-white p-5" data-scanner data-endpoint="{{ route('alumno.registrar') }}">
            <h3 class="font-semibold">Registrar asistencia</h3>
            <p class="text-sm">Para probar sin celular, escribe o pega el código que aparece debajo del QR del docente, por ejemplo 2-A1B2C3D4E5. Necesitas pertenecer al grupo y estar dentro del horario.</p>
            <p class="text-sm">Puedes usar la cámara de tu teléfono para abrir el enlace del QR e iniciar sesión. También puedes intentar escanear aquí o pegar el enlace.</p>
            <button type="button" data-camera class="rounded bg-blue-700 px-4 py-2 text-white">Activar cámara</button>
            <button type="button" data-stop class="rounded border px-4 py-2">Detener cámara</button>
            <video data-video playsinline muted hidden class="w-full rounded"></video>
            <form data-scan-form class="space-y-3">
                <label for="qr">Código manual, enlace o contenido del QR</label>
                <textarea id="qr" data-qr required class="block w-full rounded border-slate-300"></textarea>
                <x-primary-button>Registrar asistencia</x-primary-button>
            </form>
            <p data-status role="status" aria-live="polite"></p>
        </section>
        <x-asistencia-grafica :conteo="$conteo" />
        <section class="space-y-3 rounded-lg bg-white p-5">
            <h3 class="font-semibold">Mi historial</h3>
            @forelse ($registros as $registro)
                <p class="border-t py-3">{{ $registro->clase->fecha->format('d/m/Y') }} · {{ $registro->clase->materia->nombre }} · {{ ucfirst($registro->estado) }}</p>
            @empty <p>Todavía no tienes registros. No se muestran faltas hasta que se registren al cerrar las clases.</p> @endforelse
            {{ $registros->links() }}
        </section>
    </div>
    @vite('resources/js/scanner.js')
</x-app-layout>
