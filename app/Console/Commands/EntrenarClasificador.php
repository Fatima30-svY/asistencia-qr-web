<?php

namespace App\Console\Commands;

use App\Services\ClasificadorBayesiano;
use Illuminate\Console\Command;

class EntrenarClasificador extends Command
{
    protected $signature = 'clasificador:entrenar';

    protected $description = 'Entrena el clasificador bayesiano con el historial de asistencias';

    public function handle(ClasificadorBayesiano $clasificador): int
    {
        $modelo = $clasificador->entrenar();

        $this->info('Modelo entrenado.');
        $this->line('Registros que asistieron: '.$modelo['clases']['asiste']);
        $this->line('Registros con falta: '.$modelo['clases']['falta']);

        return self::SUCCESS;
    }
}