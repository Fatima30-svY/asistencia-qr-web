<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'matricula',
        'grupo_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'aprobado_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'alumno_id');
    }

    public function clasesImpartidas(): HasMany
    {
        return $this->hasMany(Clase::class, 'docente_id');
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esDocente(): bool
    {
        return $this->rol === 'docente';
    }

    public function esAlumno(): bool
    {
        return $this->rol === 'alumno';
    }
}
