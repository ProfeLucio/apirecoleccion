<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Posicion;
use App\Models\Recorrido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosicionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/recorridos/{recorrido}/posiciones",
     *     summary="Listar posiciones de un recorrido",
     *     description="Devuelve el historial de posiciones (GeoJSON) de un recorrido específico.",
     *     tags={"Posiciones"},
     *     @OA\Parameter(
     *         name="recorrido",
     *         in="path",
     *         required=true,
     *         description="ID del recorrido",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de posiciones",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Posicion"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recorrido no encontrado",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Recorrido no encontrado"))
     *     )
     * )
     */
    public function index(Recorrido $recorrido)
    {
        // Seleccionamos los datos y convertimos la geometría a GeoJSON
        $posiciones = $recorrido->posiciones()
            ->select('id', 'capturado_ts', DB::raw('ST_AsGeoJSON(geom) as geom'))
            ->orderBy('capturado_ts', 'asc') // Ordenamos por fecha de captura
            ->get();

        return $posiciones;
    }

    /**
     * @OA\Post(
     *     path="/api/recorridos/{recorrido}/posiciones",
     *     summary="Registrar una nueva posición",
     *     description="Registra una nueva posición (lat/lon) para un recorrido, enviada por la app móvil.",
     *     tags={"Posiciones"},
     *     @OA\Parameter(
     *         name="recorrido",
     *         in="path",
     *         required=true,
     *         description="ID del recorrido",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lat","lon"},
     *             @OA\Property(property="lat", type="number", format="float", example=3.42158),
     *             @OA\Property(property="lon", type="number", format="float", example=-76.5205)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Posición registrada",
     *         @OA\JsonContent(ref="#/components/schemas/Posicion")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="El campo lat es obligatorio."))
     *     )
     * )
     */
    public function store(Request $request, Recorrido $recorrido)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        $posicion = $recorrido->posiciones()->create([
            'capturado_ts' => now(),
            // ST_MakePoint(longitud, latitud)
            'geom' => DB::raw("ST_SetSRID(ST_MakePoint({$request->lon}, {$request->lat}), 4326)"),
        ]);

        return response()->json($posicion, 201);
    }
}
