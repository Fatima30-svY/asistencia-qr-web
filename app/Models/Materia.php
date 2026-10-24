<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    protected $fillable = ['nombre', 'semestre', 'clave', 'descripcion'];

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'grupo_materia');
    }

    public function clases(): HasMany
    {
        return $this->hasMany(Clase::class);
    }
}
