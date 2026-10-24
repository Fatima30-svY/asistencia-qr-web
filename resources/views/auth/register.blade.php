<x-guest-layout>
    <h1 class="text-xl font-semibold text-slate-900">Crear cuenta</h1>
    <p class="mb-6 mt-1 text-sm text-slate-500">Las cuentas docentes requieren aprobación del director. Los alumnos recibirán su grupo desde la administración.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-4">
            <x-input-label for="rol" value="Tipo de cuenta" />
            <select id="rol" name="rol" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                <option value="" @selected(old('rol', '') === '')>Selecciona tu rol</option>
                @if ($permiteDirector)
                    <option value="admin" @selected(old('rol') === 'admin')>Director / Administrador inicial</option>
                @endif
                <option value="docente" @selected(old('rol') === 'docente')>Docente</option>
                <option value="alumno" @selected(old('rol') === 'alumno')>Alumno</option>
            </select>
            <x-input-error :messages="$errors->get('rol')" class="mt-2" />
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nombre completo" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                ¿Ya tienes cuenta? Inicia sesión
            </a>

            <x-primary-button class="ms-4">
                Registrarme
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
