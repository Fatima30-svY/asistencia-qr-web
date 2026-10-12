<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\User;
use Carbon\Carbon;
use DomainException;

/**
 * Sistema de reglas normales (se evalúan en orden).
 *
 * R1. El QR debe coincidir con el de la clase y no estar vencido.
 * R2. El alumno debe pertenecer al grupo de la clase.
 * R3. Un alumno solo puede registrarse una vez por clase.
 * R4. Si la clase ya terminó, se rechaza el registro.
 * R5. Escaneo hasta (hora_inicio + tolerancia)  -> ASISTENCIA.
 * R6. Escaneo después de la tolerancia y antes del fin -> RETARDO.
 */
class ReglasAsistencia
{
    public function __construct(private ExcepcionesAsistencia $excepciones)
    {
    }

    public function registrarEscaneo(Clase $clase, User $alumno, string $token, ?Carbon $ahora = null): Asistencia
    {
        $ahora ??= now();

        // R1
        if (! $clase->token_qr || ! hash_equals($clase->token_qr, $token)) {

            throw new DomainException('El código QR no es válido para esta clase.');
        }
        if ($clase->qr_expira_at && $ahora->greaterThan($clase->qr_expira_at)) {
            throw new DomainException('El código QR ya venció.');
        }

        // R2
        if ($alumno->grupo_id !== $clase->grupo_id) {
            throw new DomainException('No perteneces al grupo de esta clase.');
        }

        // R3
        if (Asistencia::where('clase_id', $clase->id)->where('alumno_id', $alumno->id)->exists()) {
            throw new DomainException('Ya registraste tu asistencia en esta clase.');
        }

        // R4
        if ($ahora->greaterThan($this->fin($clase))) {
            throw new DomainException('La clase ya terminó.');
        }

        // R5 y R6
        $limite = $this->inicio($clase)->copy()->addMinutes($clase->tolerancia_min);
        $estado = $ahora->lessThanOrEqualTo($limite) ? 'asistencia' : 'retardo';

        return Asistencia::create([
            'clase_id'     => $clase->id,
            'alumno_id'    => $alumno->id,
            'escaneado_at' => $ahora,
            'estado'       => $estado,
        ]);
    }


    /**
     * Cierra la clase: a quien no escaneó le aplica la regla general
     * (falta) pasando primero por las excepciones.
     * Devuelve cuántos registros creó.
     */
    public function cerrarClase(Clase $clase): int
    {
        $creados = 0;

        $alumnos = User::where('rol', 'alumno')
            ->where('grupo_id', $clase->grupo_id)
            ->get();

        foreach ($alumnos as $alumno) {
            $yaTiene = Asistencia::where('clase_id', $clase->id)
                ->where('alumno_id', $alumno->id)
                ->exists();

            if ($yaTiene) {
                continue;
            }

            $estado = $this->excepciones->estadoSinEscaneo($alumno, $clase);

            if ($estado === null) {
                continue; // la clase no cuenta para este alumno
            }

            Asistencia::create([
                'clase_id'  => $clase->id,
                'alumno_id' => $alumno->id,

                'estado'    => $estado,
            ]);
            $creados++;
        }

        return $creados;
    }

    private function inicio(Clase $clase): Carbon
    {
        return Carbon::parse($clase->fecha->toDateString().' '.$clase->hora_inicio);
    }

    private function fin(Clase $clase): Carbon
    {
        return Carbon::parse($clase->fecha->toDateString().' '.$clase->hora_fin);
    }
}