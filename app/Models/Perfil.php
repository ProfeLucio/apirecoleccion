<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 * schema="Perfil",
 * required={"id", "nombre_perfil"},
 * @OA\Property(property="id", type="string", format="uuid", description="ID único del perfil"),
 * @OA\Property(property="nombre_perfil", type="string", description="Nombre del perfil de trabajo", example="Perfil 1"),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de última actualización")
 * )
 */
class Perfil extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'perfiles'; // <-- AÑADE ESTA LÍNEA

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre_perfil'
    ];



}
