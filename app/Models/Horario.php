<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'ruta_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}
