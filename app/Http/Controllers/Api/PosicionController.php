<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recorrido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosicionController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/recorridos/{recorrido}/posiciones",
     * summary="Listar posiciones de un recorrido",
     * tags={"Posiciones"},
     * @OA\Parameter(
     * name="recorrido",
     * in="path",
     * required=true,
     * description="ID del recorrido",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="UUID del perfil propietario para verificar el permiso.",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(response=200, description="Listado de posiciones."),
     * @OA\Response(response=403, description="Acción no autorizada."),
     * @OA\Response(response=404, description="Recorrido no encontrado.")
     * )
     */
    public function index(Request $request, Recorrido $recorrido)
    {
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        // --- ¡Verificación de seguridad clave! ---
        if ($recorrido->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado para ver estas posiciones.'], 403);
        }

        $posiciones = $recorrido->posiciones()
            ->select('id', 'capturado_ts', DB::raw('ST_AsGeoJSON(geom) as geom'))
            ->orderBy('capturado_ts', 'asc')
            ->get();

        return response()->json(['data' => $posiciones]);
    }

    /**
     * @OA\Post(
     * path="/api/recorridos/{recorrido}/posiciones",
     * summary="Registrar una nueva posición",
     * tags={"Posiciones"},
     * @OA\Parameter(
     * name="recorrido",
     * in="path",
     * required=true,
     * description="ID del recorrido",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"lat", "lon", "perfil_id"},
     * @OA\Property(property="lat", type="number", format="float", example=3.42158),
     * @OA\Property(property="lon", type="number", format="float", example=-76.5205),
     * @OA\Property(property="perfil_id", type="string", format="uuid")
     * )
     * ),
     * @OA\Response(response=201, description="Posición registrada."),
     * @OA\Response(response=403, description="Acción no autorizada."),
     * @OA\Response(response=422, description="Validación fallida.")
     * )
     */
    public function store(Request $request, Recorrido $recorrido)
    {
        $validatedData = $request->validate([
            'lat'       => 'required|numeric|between:-90,90',
            'lon'       => 'required|numeric|between:-180,180',
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        // --- ¡Verificación de seguridad clave! ---
        if ($recorrido->perfil_id !== $validatedData['perfil_id']) {
            return response()->json(['error' => 'No autorizado para añadir posiciones a este recorrido.'], 403);
        }

        $posicion = $recorrido->posiciones()->create([
            'perfil_id'    => $validatedData['perfil_id'],
            'capturado_ts' => now(),
            'geom'         => DB::raw("ST_SetSRID(ST_MakePoint({$validatedData['lon']}, {$validatedData['lat']}), 4326)"),
        ]);

        // Para devolver la geometría en formato GeoJSON directamente
        $posicion->geom = json_decode(DB::selectOne('SELECT ST_AsGeoJSON(?) as geom', [$posicion->geom])->geom);

        return response()->json($posicion, 201);
    }
}
