<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <style>[x-cloak] { display: none !important; }</style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800">
        <div x-data="{ open: false }" class="min-h-screen">

            <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-20 bg-slate-900/50 lg:hidden"></div>

            @include('layouts.navigation')

            <div class="lg:pl-64">
                <div class="sticky top-0 z-10 flex h-14 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:hidden">
                    <button type="button" @click="open = true" class="rounded-md p-2 text-slate-600 hover:bg-slate-100" aria-label="Abrir menú">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>
                    <span class="font-semibold text-slate-900">Asistencia QR</span>
                </div>

                @isset($header)
                    <header class="border-b border-slate-200 bg-white">
                        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
        <x-asistente-escolar />
    </body>
</html>
