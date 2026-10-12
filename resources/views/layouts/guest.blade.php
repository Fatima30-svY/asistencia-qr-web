<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800">
        <div class="flex min-h-screen flex-col items-center justify-center px-4">
            <a href="/" class="mb-6 flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-md bg-blue-700 font-bold text-white">QR</span>
                <span class="text-xl font-semibold text-slate-900">Asistencia QR</span>
            </a>

            <div class="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:max-w-md sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>