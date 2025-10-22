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

        // --- ¡Verificación de seguridad clave! ---
        if ($recorrido->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado para ver estas posiciones.'], 403);
        }

        $posiciones = Posicion::where('recorrido_id', $recorrido->id)
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
    // 1. Validar los datos de entrada
    $validatedData = $request->validate([
        'lat'       => 'required|numeric|between:-90,90',
        'lon'       => 'required|numeric|between:-180,180',
        'perfil_id' => 'required|uuid|exists:perfiles,id'
    ]);

    // 2. Verificación de Seguridad y Estado (usando el modelo inyectado)
    // Verifica que el perfil enviado en el cuerpo coincida con el perfil dueño del recorrido.
    if ($recorrido->perfil_id !== $validatedData['perfil_id']) {
        return response()->json(['error' => 'No autorizado para añadir posiciones a este recorrido. El perfil no coincide.'], 403);
    }

    // Verifica que el recorrido esté activo.
    if ($recorrido->estado !== 'En Curso') {
        return response()->json(['error' => 'El recorrido debe estar "En Curso" para añadir posiciones.'], 403);
    }

    // 3. Crear la posición
    try {
        // Usa la relación para asignar automáticamente recorrido_id
        $posicion = $recorrido->posiciones()->create([
            'perfil_id'    => $validatedData['perfil_id'],
            'capturado_ts' => now(),

            // CONSTRUCCIÓN SEGURA DE LA GEOMETRÍA: Usando bindings (?) con DB::raw
            'geom'         => DB::raw("ST_SetSRID(ST_MakePoint(?, ?), 4326)", [
                $validatedData['lon'],
                $validatedData['lat']
            ]),
        ]);

        // 4. Formatear la respuesta
        // Ya que eliminamos el accesor del modelo, debemos consultar el registro
        // de nuevo para obtener el GeoJSON formateado para la respuesta JSON.
        $posicionFormateada = DB::table('posiciones')
            ->where('id', $posicion->id)
            ->select(
                'id',
                'recorrido_id',
                'perfil_id',
                'capturado_ts',
                DB::raw('ST_AsGeoJSON(geom) as geom_geojson') // Campo GeoJSON
            )
            ->first();

        // Si el registro se crea correctamente, devolvemos el registro formateado.
        return response()->json($posicionFormateada, Response::HTTP_CREATED);

    } catch (\Exception $e) {
        // Manejo de errores de MassAssignment o QueryException
        \Log::error('Error al registrar la posición:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

        return response()->json([
            'message' => 'Error interno al registrar la posición. Revise los logs del servidor.',
            'error_details' => app()->hasDebugMode() && config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
}
