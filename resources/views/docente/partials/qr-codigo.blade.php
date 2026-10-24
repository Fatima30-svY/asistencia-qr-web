@if ($finalizada)
    <p class="rounded-lg bg-slate-100 p-5 font-semibold text-slate-700">{{ $clase->cerrada_at ? 'Clase cerrada' : 'El horario de la clase terminó' }}</p>
    <p class="mt-3 text-sm text-slate-500">El QR y el código manual ya no admiten registros. Cada nueva clase tendrá su propio código.</p>
@else
    <div class="inline-block max-w-full rounded-md border border-slate-200 p-3">{!! $svg !!}</div>
    <p class="mt-4 text-sm text-slate-500">Vigente hasta las {{ $clase->qr_expira_at?->format('H:i:s') }}</p>
    <div class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <label for="codigo-manual" class="block text-sm font-semibold text-blue-900">Código para escribir sin cámara</label>
        <input id="codigo-manual" data-manual-code readonly value="{{ $clase->codigoManual() }}" class="mt-3 block w-full rounded-md border-blue-200 bg-white text-center font-mono text-lg font-semibold tracking-wider" onclick="this.select()">
        <button type="button" data-copy-code class="mt-3 text-sm font-semibold text-blue-700 underline">Copiar código</button>
        <p class="mt-3 text-xs text-blue-900">En la cuenta del alumno, abre “Escanear y ver mi asistencia”, escribe este código y pulsa “Registrar asistencia”. Se aplican las mismas reglas del QR.</p>
    </div>
    <p class="mt-4 break-all text-sm"><a class="text-blue-700 underline" href="{{ route('alumno.confirmar', ['clase' => $clase, 'token' => $clase->token_qr]) }}">Enlace de asistencia para alumnos</a></p>
    <p class="mt-3 text-xs text-slate-500">El QR y el código se renuevan al vencer, cada 15 minutos como máximo y solo hasta el final de la clase.</p>
@endif
