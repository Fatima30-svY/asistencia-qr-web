<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $fillable = ['nombre', 'semestre'];

    public function alumnos(): HasMany
    {
        return $this->hasMany(User::class)->where('rol', 'alumno');
    }

    public function clases(): HasMany
    {
        return $this->hasMany(Clase::class);
    }
}