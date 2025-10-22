<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\Calle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RutaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/rutas",
     *     summary="Listar rutas por perfil",
     *     description="Devuelve todas las rutas asociadas al perfil especificado por su UUID.",
     *     tags={"Rutas"},
     *     @OA\Parameter(
     *         name="perfil_id",
     *         in="query",
     *         required=true,
     *         description="UUID del perfil",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de rutas",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Ruta"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="El campo perfil_id es obligatorio.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        $rutas = Ruta::select(
            'id',
            'perfil_id',
            'nombre_ruta',
            'color_hex',
           // DB::raw('ST_AsGeoJSON(shape) as shape')
        )
        ->where('perfil_id', $request->query('perfil_id'))
        ->get();

        return response()->json(['data' => $rutas]);
    }

   /**
 * @OA\Post(
 * path="/api/rutas",
 * summary="Crear una nueva ruta",
 * description="Crea una ruta a partir de una geometría GeoJSON o por IDs de calles. Es obligatorio enviar uno de los dos campos: 'shape' O 'calles_ids'.",
 * tags={"Rutas"},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * // ATENCIÓN: Eliminamos "shape" de 'required'
 * required={"nombre_ruta", "perfil_id"},
 * * // Usamos 'oneOf' para forzar que uno de los dos grupos de propiedades sea obligatorio
 * @OA\Schema(
 * allOf={
 * @OA\Schema(
 * @OA\Property(property="nombre_ruta", type="string", example="Ruta Personalizada 1"),
 * @OA\Property(property="perfil_id", type="string", format="uuid")
 * ),
 * @OA\Schema(
 * oneOf={
 * // OPCIÓN 1: Enviar 'shape'
 * @OA\Schema(
 * required={"shape"},
 * @OA\Property(
 * property="shape",
 * type="string", // Debe ser 'string' porque lo envías como cadena JSON
 * description="Cadena GeoJSON (LineString o Polygon). Debe ser obligatorio si calles_ids está ausente.",
 * example="{\"type\":\"LineString\",\"coordinates\":[[-77.078,3.889],[-77.060,3.882]]}"
 * ),
 * @OA\Property(
 * property="calles_ids",
 * type="array",
 * nullable=true,
 * description="Debe ser nulo o omitido si se envía 'shape'.",
 * @OA\Items(type="string", format="uuid")
 * )
 * ),
 * // OPCIÓN 2: Enviar 'calles_ids'
 * @OA\Schema(
 * required={"calles_ids"},
 * @OA\Property(
 * property="shape",
 * type="string",
 * nullable=true,
 * description="Debe ser nulo o omitido si se envía 'calles_ids'."
 * ),
 * @OA\Property(
 * property="calles_ids",
 * type="array",
 * description="Lista de UUIDs de las calles que componen la ruta. Debe ser obligatorio si 'shape' está ausente.",
 * @OA\Items(type="string", format="uuid"),
 * minItems=1
 * )
 * )
 * }
 * )
 * }
 * )
 * )
 * ),
 * @OA\Response(response=201, description="Ruta creada"),
 * @OA\Response(response=422, description="Validación fallida: Se debe enviar 'shape' o 'calles_ids'.")
 * )
 */
    public function store(Request $request)
    {
        // 1. VALIDACIÓN CONDICIONAL (Exclusión Mutua)
        $validatedData = $request->validate([
            'nombre_ruta' => 'required|string|max:255',
            'perfil_id'   => 'required|uuid|exists:perfiles,id',

            // Requisito 1: Modo Shape (Cadena GeoJSON)
            'shape'       => 'required_without:calles_ids|nullable|string|json',

            // Requisito 2: Modo Calles (Array de IDs)
            'calles_ids'  => 'required_without:shape|nullable|array|min:1',
            'calles_ids.*'=> 'uuid|exists:calles,id',
        ]);

        $caso = '';

        // 2. DETERMINAR EL CASO

        // =======================================================
        // CASO A: Modo Shape (Geometría Directa)
        // =======================================================
        if (isset($validatedData['shape']) && $validatedData['shape'] !== null) {
            $caso = 'Modo Shape (Geometría Directa)';

            // Opcional: Decodificar el JSON de prueba para ver si es válido
            $validatedData['shape_parsed'] = json_decode($validatedData['shape']);
        }

        // =======================================================
        // CASO B: Modo Calles (Unión de Geometrías)
        // =======================================================
        elseif (isset($validatedData['calles_ids']) && count($validatedData['calles_ids']) > 0) {
            $caso = 'Modo Calles (Unión de Geometrías)';

            // Si el modo Calles está activo, aseguramos que la validación de calles pasó
            // El campo 'calles_ids.*' garantiza que los IDs son válidos.
        }
        else {
            // Esto no debería suceder gracias a required_without
             $caso = 'ERROR: No se activó ningún caso (Validación fallida)';
        }
        return response()->json([
            'status' => 'OK - Validación Exitosa',
            'caso_activado' => $caso,
            'data_recibida' => $validatedData,
            'notas' => 'La lógica de la base de datos (DB::transaction) fue omitida.'
        ], 200);
    }

/**
     * @OA\Get(
     * path="/api/rutas/{id}",
     * summary="Obtener detalles de una ruta",
     * description="Devuelve los detalles de una ruta, incluyendo geometría, horarios y calles asociadas. Requiere autorización a través del perfil.",
     * tags={"Rutas"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID de la ruta",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="ID del perfil propietario para validar autorización",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Detalles de la ruta",
     * @OA\JsonContent(ref="#/components/schemas/Ruta")
     * ),
     * @OA\Response(
     * response=403,
     * description="Acceso no autorizado",
     * @OA\JsonContent(@OA\Property(property="error", type="string", example="No autorizado para ver esta ruta."))
     * ),
     * @OA\Response(
     * response=404,
     * description="Ruta no encontrada"
     * )
     * )
     */
    public function show(Ruta $ruta, Request $request)
    {
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        if ($ruta->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado para ver esta ruta.'], 403);
        }

        $rutaConGeometria = Ruta::select(
            'id',
            'perfil_id',
            'nombre_ruta',
            'color_hex',
            'created_at',
            'updated_at',
            DB::raw('ST_AsGeoJSON(shape) as shape')
        )
        ->where('id', $ruta->id)
        ->with(['horarios', 'calles' => function ($query) {
            // CORRECCIÓN 3: Asegurar que la geometría de las calles también se devuelva como GeoJSON
            $query->select('calles.id', 'nombre', DB::raw('ST_AsGeoJSON(shape) as shape'))
                  ->orderBy('orden');
        }])
        ->firstOrFail();

        return response()->json($rutaConGeometria);
    }
}
