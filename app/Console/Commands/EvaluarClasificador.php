<?php

namespace App\Console\Commands;

use App\Services\ClasificadorBayesiano;
use Illuminate\Console\Command;

class EvaluarClasificador extends Command
{
    protected $signature = 'clasificador:evaluar';

    protected $description = 'Mide la exactitud del clasificador entrenando con el pasado y probando con lo más reciente';

    public function handle(ClasificadorBayesiano $clasificador): int
    {
        $configuraciones = [
            'Todas las características' => [],
            'Sin retardos' => ['retardos'],
            'Sin tasa histórica' => ['tasa'],
            'Sin día ni franja' => ['dia', 'franja'],
            'Solo faltas y tasa' => ['retardos', 'dia', 'franja'],
        ];

        $filas = [];
        $base = null;

        foreach ($configuraciones as $nombre => $excluir) {
            $r = $clasificador->validar($excluir);

            if (! $r) {
                $this->error('No hay suficiente historial para evaluar.');


                return self::FAILURE;
            }

            $base = $r;
            $filas[] = [
                $nombre,
                number_format($r['exactitud'] * 100, 1).'%',
                number_format($r['precision'] * 100, 1).'%',
                number_format($r['recall'] * 100, 1).'%',
            ];
        }

        $this->info("Entrenado con registros anteriores al {$base['corte']}.");
        $this->info("Probado con {$base['pruebas']} registros, de los cuales {$base['faltas_reales']} fueron faltas.");
        $this->info('Exactitud de "siempre predecir que asiste": '.number_format($base['base'] * 100, 1).'%');
        $this->newLine();

        $this->table(['Configuración', 'Exactitud', 'Precisión', 'Recall'], $filas);

        return self::SUCCESS;
    }
}