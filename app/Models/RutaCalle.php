<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RutaCalle extends Pivot
{

protected $casts = [
    'coordenadas' => 'array',
];
    protected $fillable = [
        'ruta_id',
        'calle_id',
        'orden',
        'coordenadas', // Agrega el nuevo campo aquí
    ];

    // Indica que 'coordenadas' es un tipo de dato espacial
}
