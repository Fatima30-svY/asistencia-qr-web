<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold">Confirmar asistencia</h2></x-slot>
    <div class="mx-auto max-w-xl space-y-4 p-6">
        <p>{{ $clase->materia->nombre }} · {{ $clase->grupo->nombre }} · {{ $clase->fecha->format('d/m/Y') }}</p>
        @foreach ($errors->all() as $error) <p role="alert" class="text-red-700">{{ $error }}</p> @endforeach
        <p>Se comprobarán tu grupo, el horario y la vigencia del QR. Abrir este enlace no registra asistencia hasta confirmar.</p>
        <form method="POST" action="{{ route('alumno.registrar') }}">
            @csrf
            <input type="hidden" name="clase_id" value="{{ $clase->id }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <x-primary-button>Registrar mi asistencia</x-primary-button>
        </form>
    </div>
</x-app-layout>
