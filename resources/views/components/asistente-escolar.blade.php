<div data-assistant data-endpoint="{{ route('asistente.preguntar') }}" class="fixed bottom-5 right-4 z-40 sm:right-6">
    <button type="button" data-assistant-toggle aria-expanded="false" aria-controls="asistente-panel" class="rounded-full bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:bg-blue-800">¿Necesitas ayuda? · IA</button>
    <section id="asistente-panel" data-assistant-panel hidden role="dialog" aria-labelledby="asistente-titulo" class="absolute bottom-16 right-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" style="width:min(24rem,calc(100vw - 2rem));height:min(34rem,75dvh)">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 p-4">
            <div><h2 id="asistente-titulo" class="font-semibold text-slate-900">Asistente escolar</h2><p class="mt-1 text-xs text-slate-500">IA local · Respuestas breves</p></div>
            <button type="button" data-assistant-close aria-label="Cerrar asistente" class="rounded p-2 text-slate-600">✕</button>
        </header>
        <div data-assistant-messages role="log" aria-live="polite" aria-label="Conversación" class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4">
            <p class="rounded-lg bg-slate-100 p-3 text-sm text-slate-700">Hola. Puedo orientarte sobre el sistema y dudas académicas breves. Mis respuestas pueden contener errores; no cambio registros ni consulto expedientes. No compartas contraseñas ni datos personales.</p>
        </div>
        <div class="flex items-center justify-between gap-2 px-4 py-2 text-xs">
            <button type="button" data-assistant-example class="text-blue-700 underline">{{ match(auth()->user()->rol) { 'admin' => '¿Cómo asigno alumnos a un grupo?', 'docente' => '¿Cómo cierro una clase?', default => '¿Cómo registro mi asistencia sin cámara?' } }}</button>
            <button type="button" data-assistant-clear class="text-slate-500 underline">Limpiar</button>
        </div>
        <form data-assistant-form class="space-y-2 border-t border-slate-200 p-4">
            <label for="asistente-pregunta" class="sr-only">Tu pregunta</label>
            <textarea id="asistente-pregunta" data-assistant-input rows="2" required maxlength="600" placeholder="Escribe tu pregunta…" class="block w-full resize-none rounded-lg border-slate-300 text-sm"></textarea>
            <div class="flex items-center justify-between gap-3"><p data-assistant-status role="status" class="text-xs text-slate-500"></p><button data-assistant-send class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Enviar</button></div>
        </form>
    </section>
</div>
