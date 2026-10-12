<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $fillable = [
        'clase_id',
        'alumno_id',
        'escaneado_at',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'escaneado_at' => 'datetime',
        ];
    }

    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

}