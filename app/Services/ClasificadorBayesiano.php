<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Clasificador Naive Bayes con suavizado de Laplace.
 *
 * Clases: 'asiste' (asistencia o retardo) y 'falta'.
 * Las asistencias justificadas no se usan: no son ausentismo real.
 *
 * Características (todas categóricas):
 *  - faltas:   faltas en las últimas 10 clases del alumno (0, 1, 2, 3+)
 *  - retardos: retardos en las últimas 10 clases (0, 1, 2, 3+)
 *  - tasa:     porcentaje de faltas en todo su historial (baja, media, alta)
 *  - dia:      día de la semana (si cae en fin de semana se omite)
 *  - franja:   temprano (antes de 10), medio (10 a 13) o tarde (13 en adelante)
 */
class ClasificadorBayesiano
{
    private const VENTANA = 10;
    private const MIN_HISTORIAL = 3;
    private const CLASES = ['asiste', 'falta'];
    private const DIAS = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes'];

    private const VALORES = [
        'faltas'   => ['0', '1', '2', '3+'],

        'retardos' => ['0', '1', '2', '3+'],
        'tasa'     => ['baja', 'media', 'alta'],
        'dia'      => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'],
        'franja'   => ['temprano', 'medio', 'tarde'],
    ];

    private const ARCHIVO = 'modelo_bayes.json';
    private const UMBRAL_MEDIO = 0.15;
    private const UMBRAL_ALTO = 0.30;

    /**
     * Aprende del historial. Con $hasta solo usa registros anteriores a esa fecha;
     * con $excluir ignora esas características; con $guardar = false no escribe el archivo.
     */
    public function entrenar(?Carbon $hasta = null, array $excluir = [], bool $guardar = true): array
    {
        $totalClase = ['asiste' => 0, 'falta' => 0];
        $conteo = [];

        foreach (self::CLASES as $c) {
            foreach (self::VALORES as $carac => $valores) {
                foreach ($valores as $v) {
                    $conteo[$c][$carac][$v] = 0;
                }
            }
        }

        foreach ($this->historialesPorAlumno() as $registros) {
            foreach ($registros as $i => $registro) {
                if ($i < self::MIN_HISTORIAL) {
                    continue;
                }


                if ($hasta && $this->momentoDe($registro)->gte($hasta)) {
                    continue;
                }

                $x = $this->caracteristicasDe($registros, $i, $excluir);

                $clase = $registro->estado === 'falta' ? 'falta' : 'asiste';
                $totalClase[$clase]++;

                foreach ($x as $carac => $valor) {
                    $conteo[$clase][$carac][$valor]++;
                }
            }
        }

        $modelo = [
            'clases' => $totalClase,
            'conteo' => $conteo,
            'entrenado_at' => now()->toDateTimeString(),
        ];

        if ($guardar) {
            Storage::disk('local')->put(self::ARCHIVO, json_encode($modelo, JSON_PRETTY_PRINT));
        }

        return $modelo;
    }

    /**
     * Mide qué tan bien predice el modelo: entrena con el primer 70% del tiempo
     * del historial y prueba con el 30% restante.

     */
    public function validar(array $excluir = []): ?array
    {
        $todos = $this->historialesPorAlumno();

        $min = PHP_INT_MAX;
        $max = 0;

        foreach ($todos as $registros) {
            foreach ($registros as $r) {
                $t = $this->momentoDe($r)->timestamp;
                $min = min($min, $t);
                $max = max($max, $t);
            }
        }

        if ($max <= $min) {
            return null;
        }

        $corte = Carbon::createFromTimestamp($min + (int) (($max - $min) * 0.7));
        $modelo = $this->entrenar($corte, $excluir, false);

        $vp = $fp = $vn = $fn = 0;

        foreach ($todos as $registros) {
            foreach ($registros as $i => $registro) {
                if ($i < self::MIN_HISTORIAL || $this->momentoDe($registro)->lt($corte)) {
                    continue;
                }

                $x = $this->caracteristicasDe($registros, $i, $excluir);

                $predijoFalta = $this->probabilidad($modelo, $x) >= self::UMBRAL_ALTO;
                $fueFalta = $registro->estado === 'falta';

                if ($predijoFalta && $fueFalta) {
                    $vp++;
                } elseif ($predijoFalta) {
                    $fp++;
                } elseif ($fueFalta) {
                    $fn++;
                } else {
                    $vn++;
                }
            }
        }

        $total = $vp + $fp + $vn + $fn;

        if ($total === 0) {
            return null;
        }

        return [
            'corte' => $corte->toDateString(),
            'pruebas' => $total,
            'faltas_reales' => $vp + $fn,
            'exactitud' => ($vp + $vn) / $total,
            'precision' => ($vp + $fp) > 0 ? $vp / ($vp + $fp) : 0,
            'recall' => ($vp + $fn) > 0 ? $vp / ($vp + $fn) : 0,
            'base' => ($vn + $fp) / $total, // exactitud de "siempre predecir que asiste"
        ];
    }


    public function tieneModelo(): bool
    {
        return Storage::disk('local')->exists(self::ARCHIVO);
    }

    /**
     * Probabilidad de que el alumno falte a una clase en esa fecha y hora,
     * usando solo su historial anterior a ese momento.
     */
    public function evaluar(User $alumno, Carbon $fecha, string $horaInicio): ?array
    {
        $modelo = $this->modelo();

        if (! $modelo) {
            return null;
        }

        $momento = Carbon::parse($fecha->toDateString().' '.$horaInicio);

        $historial = Asistencia::with('clase')
            ->where('alumno_id', $alumno->id)
            ->where('estado', '!=', 'justificada')
            ->get()
            ->filter(fn ($a) => $this->momentoDe($a)->lt($momento))
            ->sortBy(fn ($a) => $this->momentoDe($a)->timestamp)
            ->values();

        $previos = $historial->slice(-self::VENTANA);

        $x = $this->caracteristicas($previos, $historial, $fecha, $horaInicio);
        $probFalta = $this->probabilidad($modelo, $x);


        return [
            'probabilidad' => $probFalta,
            'riesgo' => $this->nivel($probFalta),
            'x' => $x,
        ];
    }

    /** P(falta | características) con Naive Bayes y suavizado de Laplace. */
    private function probabilidad(array $modelo, array $x): float
    {
        $total = array_sum($modelo['clases']);
        $logs = [];

        foreach (self::CLASES as $c) {
            // P(clase)
            $logp = log(($modelo['clases'][$c] + 1) / ($total + count(self::CLASES)));

            // P(característica | clase)
            foreach ($x as $carac => $valor) {
                if (! isset($modelo['conteo'][$c][$carac][$valor])) {
                    continue; // característica que el modelo guardado aún no conoce
                }

                $k = count(self::VALORES[$carac]);
                $logp += log(($modelo['conteo'][$c][$carac][$valor] + 1) / ($modelo['clases'][$c] + $k));
            }

            $logs[$c] = $logp;
        }

        // Normalizar las dos probabilidades para que sumen 1
        $max = max($logs);

        $exp = array_map(fn ($l) => exp($l - $max), $logs);

        return $exp['falta'] / array_sum($exp);
    }

    private function nivel(float $p): string
    {
        if ($p >= self::UMBRAL_ALTO) {
            return 'alto';
        }

        return $p >= self::UMBRAL_MEDIO ? 'medio' : 'bajo';
    }

    /** Registros de cada alumno (sin justificadas), en orden cronológico. */
    private function historialesPorAlumno(): Collection
    {
        return Asistencia::with('clase')
            ->where('estado', '!=', 'justificada')
            ->get()
            ->groupBy('alumno_id')
            ->map(fn ($registros) => $registros
                ->sortBy(fn ($a) => $this->momentoDe($a)->timestamp)
                ->values());
    }

    /** Características del registro número $i, usando solo lo anterior a él. */
    private function caracteristicasDe(Collection $registros, int $i, array $excluir): array
    {
        $historial = $registros->slice(0, $i);
        $previos = $historial->slice(-self::VENTANA);
        $registro = $registros[$i];


        return $this->caracteristicas(
            $previos,
            $historial,
            $registro->clase->fecha,
            $registro->clase->hora_inicio,
            $excluir
        );
    }

    private function caracteristicas(
        Collection $previos,
        Collection $historial,
        Carbon $fecha,
        string $hora,
        array $excluir = []
    ): array {
        $x = [
            'faltas'   => $this->cubeta($previos->where('estado', 'falta')->count()),
            'retardos' => $this->cubeta($previos->where('estado', 'retardo')->count()),
            'franja'   => $this->franja($hora),
        ];

        // Tasa histórica de faltas (solo si hay suficiente historial)
        if ($historial->count() >= self::MIN_HISTORIAL) {
            $tasa = $historial->where('estado', 'falta')->count() / $historial->count();
            $x['tasa'] = $tasa < 0.10 ? 'baja' : ($tasa < 0.25 ? 'media' : 'alta');
        }

        // Fin de semana: se omite el día (Naive Bayes ignora lo que no conoce)
        if (isset(self::DIAS[$fecha->dayOfWeekIso])) {
            $x['dia'] = self::DIAS[$fecha->dayOfWeekIso];

        }

        return array_diff_key($x, array_flip($excluir));
    }

    private function cubeta(int $n): string
    {
        return $n >= 3 ? '3+' : (string) $n;
    }

    private function franja(string $hora): string
    {
        $h = (int) substr($hora, 0, 2);

        if ($h < 10) {
            return 'temprano';
        }

        return $h < 13 ? 'medio' : 'tarde';
    }

    private function momentoDe(Asistencia $a): Carbon
    {
        return Carbon::parse($a->clase->fecha->toDateString().' '.$a->clase->hora_inicio);
    }

    private function modelo(): ?array
    {
        if (! $this->tieneModelo()) {
            return null;
        }


        return json_decode(Storage::disk('local')->get(self::ARCHIVO), true);
    }
}