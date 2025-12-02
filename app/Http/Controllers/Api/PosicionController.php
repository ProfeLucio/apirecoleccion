<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recorrido;
use App\Models\Posicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
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

        if ($recorrido->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $posiciones = Posicion::where('recorrido_id', $recorrido->id)
            // --- AQUÍ ESTÁ LA CLAVE ---
            ->select(
                'id',
                'recorrido_id',
                'perfil_id',
                'capturado_ts',
                // Convierte el binario a String JSON: '{"type":"Point", "coordinates":[...]}'
                \Illuminate\Support\Facades\DB::raw('ST_AsGeoJSON(geom) as geom')
            )
            // ---------------------------
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
    $validated = $request->validate([
        'lat'       => 'required|numeric|between:-90,90',
        'lon'       => 'required|numeric|between:-180,180',
        'perfil_id' => 'required|uuid|exists:perfiles,id',
    ]);

    if ($recorrido->perfil_id !== $validated['perfil_id']) {
        return response()->json(['error' => 'No autorizado para añadir posiciones a este recorrido.'], 403);
    }
    if ($recorrido->estado !== 'En Curso') {
        return response()->json(['error' => 'El recorrido debe estar "En Curso" para añadir posiciones.'], 403);
    }

    try {
        $posicion = null;

        DB::transaction(function () use ($validated, $recorrido, &$posicion) {
            // 1) Crear SIN la geom (evitas mass assignment y problemas con DB::raw)
            $posicion = \App\Models\Posicion::create([
                'recorrido_id' => $recorrido->id,
                'perfil_id'    => $validated['perfil_id'],
                'capturado_ts' => now(),
            ]);

            // 2) Actualizar SOLO la geom con bindings (seguro)
            DB::update(
                'UPDATE posiciones
                 SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326)
                 WHERE id = ?',
                [
                    // OJO: PostGIS usa X=lon, Y=lat
                    (float)$validated['lon'],
                    (float)$validated['lat'],
                    $posicion->id,
                ]
            );
        });

        // 3) Devolver con GeoJSON (evita serializar geometry crudo)
        $out = DB::table('posiciones')
            ->where('id', $posicion->id)
            ->select(
                'id','recorrido_id','perfil_id','capturado_ts',
                DB::raw('ST_AsGeoJSON(geom) AS geom_geojson')
            )
            ->first();

        return response()->json($out, Response::HTTP_CREATED);

    } catch (\Throwable $e) {
        Log::error('Error al registrar la posición', ['msg'=>$e->getMessage(), 'line'=>$e->getLine()]);
        return response()->json(['message'=>'Error crítico al registrar la posición.'], 500);
    }
}
}
