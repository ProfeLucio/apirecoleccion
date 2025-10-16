<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 * schema="Posicion",
 * required={"id", "recorrido_id", "geom", "capturado_ts"},
 * @OA\Property(property="id", type="string", format="uuid", description="ID único del registro de posición"),
 * @OA\Property(property="recorrido_id", type="string", format="uuid", description="ID del recorrido al que pertenece el punto"),
 * @OA\Property(property="capturado_ts", type="string", format="date-time", description="Fecha y hora exactas de la captura de la coordenada"),
 * @OA\Property(property="geom", type="string", description="Geometría del punto en formato GeoJSON")
 * )
 */
class Posicion extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'posiciones';

    protected $fillable = [
        'recorrido_id',
        'perfil_id',
        'capturado_ts',
        'geom',
    ];

    public function recorrido()
    {
        return $this->belongsTo(Recorrido::class);
    }

    public function perfil()
    {
        return $this->belongsTo(Perfil::class);
    }

    protected function getGeomAttribute($value)
    {
        if ($value) {
            // Convierte el valor binario a GeoJSON usando la función de PostGIS
            $geom = DB::selectOne("SELECT ST_AsGeoJSON(?) AS geojson", [$value]);
            return $geom->geojson;
        }
        return null;
    }
}
