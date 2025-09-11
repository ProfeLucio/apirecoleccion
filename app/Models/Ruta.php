<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 * schema="Ruta",
 * required={"id", "nombre_ruta", "perfil_id", "shape"},
 * @OA\Property(property="id", type="string", format="uuid", description="ID único de la ruta"),
 * @OA\Property(property="perfil_id", type="string", format="uuid", description="ID del perfil al que pertenece la ruta"),
 * @OA\Property(property="nombre_ruta", type="string", description="Nombre de la ruta", example="Ruta Centro"),
 * @OA\Property(property="color_hex", type="string", description="Color hexadecimal para representar la ruta", example="#FF5733"),
 * @OA\Property(property="shape", type="string", description="Geometría de la ruta en formato GeoJSON"),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de última actualización")
 * )
 */
class Ruta extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nombre_ruta',
        'color_hex',
        'shape',
        'perfil_id',
    ];

    public function calles()
    {
        // Una ruta está compuesta por muchas calles, a través de la tabla pivote
        return $this->belongsToMany(Calle::class, 'ruta_calle')->withPivot('orden');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }
}
