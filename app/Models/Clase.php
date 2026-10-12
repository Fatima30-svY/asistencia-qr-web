<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clase extends Model
{
    protected $fillable = [
        'materia_id',
        'grupo_id',
        'docente_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'tolerancia_min',
        'token_qr',
        'qr_expira_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'qr_expira_at' => 'datetime',
        ];
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }
}