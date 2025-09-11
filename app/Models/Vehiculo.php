<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 * schema="Vehiculo",
 * required={"id", "placa", "perfil_id"},
 * @OA\Property(property="id", type="string", format="uuid", description="ID único del vehículo"),
 * @OA\Property(property="perfil_id", type="string", format="uuid", description="ID del perfil al que pertenece el vehículo"),
 * @OA\Property(property="placa", type="string", description="Placa del vehículo", example="ABC-123"),
 * @OA\Property(property="marca", type="string", description="Marca del vehículo", example="Chevrolet"),
 * @OA\Property(property="modelo", type="integer", description="Año del modelo del vehículo", example=2022),
 * @OA\Property(property="capacidad", type="number", format="float", description="Capacidad de carga en toneladas", example=5.5),
 * @OA\Property(property="tipo_combustible", type="string", description="Tipo de combustible", example="Diésel"),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de última actualización")
 * )
 */
class Vehiculo extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'activo',
    ];

    // Definimos las relaciones en el futuro
    // public function recorridos() { ... }
}
