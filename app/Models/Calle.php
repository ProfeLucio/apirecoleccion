<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 * schema="Calle",
 * required={"id", "nombre", "shape"},
 * @OA\Property(property="id", type="string", format="uuid", description="ID único de la calle"),
 * @OA\Property(property="nombre", type="string", description="Nombre de la calle", example="Calle 6"),
 * @OA\Property(property="shape", type="string", description="Geometría de la calle en formato GeoJSON")
 * )
 */
class Calle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'calles';

    protected $fillable = [
        'nombre',
        'shape',
    ];

    protected $casts = [
        'shape' => 'array',
    ];
}
