<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 * schema="Horario",
 * required={"id", "dia_semana", "hora_inicio", "hora_fin"},
 * @OA\Property(property="id", type="string", format="uuid", description="ID único del horario"),
 * @OA\Property(property="ruta_id", type="string", format="uuid", description="ID de la ruta asociada"),
 * @OA\Property(property="vehiculo_id", type="string", format="uuid", description="ID del vehículo asignado"),
 * @OA\Property(property="dia_semana", type="string", description="Día de la semana", example="Lunes"),
 * @OA\Property(property="hora_inicio", type="string", format="time", example="08:00:00"),
 * @OA\Property(property="hora_fin", type="string", format="time", example="12:00:00")
 * )
 */
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
