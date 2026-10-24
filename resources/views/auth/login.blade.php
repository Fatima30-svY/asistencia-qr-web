<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h1 class="text-xl font-semibold text-slate-900">Iniciar sesión</h1>
    <p class="mb-6 mt-1 text-sm text-slate-500">Ingresa con tu correo para registrar o consultar asistencias.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600" name="remember">
                <span class="ms-2 text-sm text-slate-600">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-blue-700 hover:underline" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

        </div>

        <x-primary-button class="w-full">Entrar</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        ¿No tienes una cuenta?
        <a class="font-medium text-blue-700 hover:underline" href="{{ route('register') }}">Regístrate</a>
    </p>
</x-guest-layout>
