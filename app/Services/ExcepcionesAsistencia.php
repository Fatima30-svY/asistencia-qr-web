<?php

namespace App\Services;

use App\Models\Clase;
use App\Models\Excepcion;
use App\Models\User;

/**
 * Sistema de reglas con excepciones.
 *
 * Regla general: si el alumno no escanea, se le pone FALTA.
 * Excepciones (anulan la regla general):
 *   - justificante, permiso  -> la falta queda como "justificada"
 *   - suspension, festivo    -> la clase no cuenta, no se registra nada
 *
 * Una excepción puede aplicar a un alumno, a un grupo completo
 * o a todos (alumno_id y grupo_id vacíos). Si hay varias a la vez,
 * la más específica gana: alumno > grupo > general.
 */
class ExcepcionesAsistencia
{
    public const NO_CUENTA = ['suspension', 'festivo'];
    public const JUSTIFICA = ['justificante', 'permiso'];

    public function buscar(User $alumno, Clase $clase): ?Excepcion
    {
        $fecha = $clase->fecha->toDateString();

        $candidatas = Excepcion::query()
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)

            ->where(function ($q) use ($alumno) {
                $q->where('alumno_id', $alumno->id)
                  ->orWhere(function ($q2) use ($alumno) {
                      $q2->whereNull('alumno_id')->where('grupo_id', $alumno->grupo_id);
                  })
                  ->orWhere(function ($q3) {
                      $q3->whereNull('alumno_id')->whereNull('grupo_id');
                  });
            })
            ->get();

        // La más específica gana
        return $candidatas->sortBy(function (Excepcion $e) {
            if ($e->alumno_id) return 0;
            if ($e->grupo_id) return 1;
            return 2;
        })->first();
    }

    /**
     * Estado final de un alumno que NO escaneó:
     * 'falta', 'justificada' o null (la clase no cuenta).
     */
    public function estadoSinEscaneo(User $alumno, Clase $clase): ?string
    {
        $excepcion = $this->buscar($alumno, $clase);

        if (! $excepcion) {
            return 'falta'; // regla general
        }

        if (in_array($excepcion->tipo, self::NO_CUENTA, true)) {

            return null;
        }

        return 'justificada';
    }
}