<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nombre_ruta',
        'color_hex',
        'shape',
        'perfil_id',
    ];

    protected $casts = [
        'shape' => 'array',
    ];

    public function calles()
    {
        return $this->belongsToMany(Calle::class, 'ruta_calle')
            ->withPivot('orden', 'coordenadas');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }
}
