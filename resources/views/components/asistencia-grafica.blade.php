@props(['conteo'])
@php $total = $conteo->sum(); @endphp
<section class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
    <h3 class="font-semibold">Gráfica de asistencia</h3>
    <p class="text-sm text-slate-600">{{ $total }} registros. Los porcentajes corresponden a los registros guardados.</p>
    @foreach (['asistencia' => '#059669', 'retardo' => '#d97706', 'falta' => '#e11d48', 'justificada' => '#2563eb'] as $estado => $color)
        @php $cantidad = (int) $conteo->get($estado, 0); $porcentaje = $total ? round($cantidad / $total * 100, 1) : 0; @endphp
        <div>
            <p class="text-sm">{{ ucfirst($estado) }}: {{ $cantidad }} ({{ $porcentaje }} %)</p>
            <div role="meter" aria-label="{{ ucfirst($estado) }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $porcentaje }}" class="mt-1 h-4 overflow-hidden rounded bg-slate-100">
                <div style="height:100%;width:{{ $porcentaje }}%;background:{{ $color }}"></div>
            </div>
        </div>
    @endforeach
</section>
