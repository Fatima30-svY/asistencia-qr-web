@php
    $usuario = auth()->user();
    $rol = $usuario->rol;

    $activo = 'bg-blue-600 text-white';
    $inactivo = 'text-slate-300 hover:bg-slate-800 hover:text-white';
@endphp

<aside :class="open ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0">

    <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-blue-600 text-sm font-bold text-white">QR</span>
        <div class="leading-tight">
            <p class="text-sm font-semibold text-white">Asistencia QR</p>
            <p class="text-xs text-slate-400">Control escolar</p>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-sm">

        <div class="space-y-1">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 rounded-md px-3 py-2 {{ request()->routeIs('dashboard') ? $activo : $inactivo }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Inicio
            </a>
        </div>

        @if ($rol === 'docente')

            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Docencia</p>
                <a href="{{ route('docente.clases') }}"
                   class="flex items-center gap-3 rounded-md px-3 py-2 {{ request()->routeIs('docente.*') ? $activo : $inactivo }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    Mis clases
                </a>
            </div>
        @endif

        @if (in_array($rol, ['admin', 'docente']))
            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Gestión</p>
                <a href="{{ route('excepciones.index') }}"
                   class="flex items-center gap-3 rounded-md px-3 py-2 {{ request()->routeIs('excepciones.*') ? $activo : $inactivo }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    Excepciones
                </a>
            </div>

            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Análisis</p>
                <a href="{{ route('riesgo.index') }}"
                   class="flex items-center gap-3 rounded-md px-3 py-2 {{ request()->routeIs('riesgo.*') ? $activo : $inactivo }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    Alumnos en riesgo

                </a>
            </div>
        @endif
    </nav>

    <div class="border-t border-slate-800 p-4">
        <a href="{{ route('profile.edit') }}" class="block text-sm font-medium text-white hover:underline">{{ $usuario->name }}</a>
        <p class="mb-3 text-xs text-slate-400">{{ ucfirst($rol) }}</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full rounded-md border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white">
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>