<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Excepcion extends Model
{
    protected $table = 'excepciones';

    protected $fillable = [
        'tipo',
        'alumno_id',
        'grupo_id',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'registrada_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }
}