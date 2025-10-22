<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\Calle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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
            DB::raw('ST_AsGeoJSON(shape) as shape')
        )
        ->where('perfil_id', $request->query('perfil_id'))
        ->get();

        return response()->json(['data' => $rutas]);
    }

/**
 * @OA\Post(
 *   path="/api/rutas",
 *   tags={"Rutas"},
 *   summary="Crear una nueva ruta",
 *   description="Envía 'shape' (GeoJSON como cadena u objeto) O 'calles_ids' (array de UUIDs). Debe venir uno de los dos.",
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"nombre_ruta","perfil_id"},
 *       @OA\Property(property="nombre_ruta", type="string", example="Ruta Solo Geometría"),
 *       @OA\Property(property="perfil_id", type="string", format="uuid", example="18851282-1a08-42b7-9384-243cc2ead349"),
 *       @OA\Property(
 *         property="shape",
 *         oneOf={
 *           @OA\Schema(type="string", description="GeoJSON como string"),
 *           @OA\Schema(type="object", description="GeoJSON como objeto")
 *         },
 *         nullable=true,
 *         description="Obligatorio si 'calles_ids' está ausente."
 *       ),
 *       @OA\Property(
 *         property="calles_ids",
 *         type="array",
 *         items=@OA\Items(type="string", format="uuid"),
 *         nullable=true,
 *         description="Obligatorio si 'shape' está ausente."
 *       )
 *     )
 *   ),
 *   @OA\Response(response=201, description="Ruta creada exitosamente."),
 *   @OA\Response(response=422, description="Validación fallida."),
 *   @OA\Response(response=500, description="Error de servidor.")
 * )
 */

    public function store(Request $request)
    {
        // 0) log de lo que está llegando (para confirmar tipos)

        try {
            // 1) validación SIN lanzar excepción automática


            $validatedData = $request->validate([
                'nombre_ruta' => 'required|string|max:255',
                'perfil_id'   => 'required|uuid|exists:perfiles,id',
                'shape'       => 'nullable',
                'calles_ids'  => 'nullable|array',
                'calles_ids.*'=> 'uuid|exists:calles,id',
            ]);

            // Validar que al menos uno de 'shape' o 'calles_ids' esté presente
            if (is_null($validatedData['shape']) && is_null($validatedData['calles_ids'])) {
                return response()->json([
                    'message' => "Se requiere al menos 'shape' o 'calles_ids'."
                ], 422);
            }
            // Crear la ruta
            $ruta = new Ruta();
            $ruta->nombre_ruta = $validatedData['nombre_ruta'];
            $ruta->perfil_id = $validatedData['perfil_id'];
            // Manejar shape si se proporciona
            if (!is_null($validatedData['shape'])) {
                $shapeJson = is_string($validatedData['shape']) ? $validatedData['shape'] : json_encode($validatedData['shape']);
                $ruta->shape = DB::raw("ST_GeomFromGeoJSON('{$shapeJson}')");
            }
            //$ruta->save();


            return response()->json(['ok' => true, 'data' => $ruta], 201);

        } catch (\Throwable $e) {
            // 2) si el 500 es *realmente* dentro de Validator::make(), lo veremos aquí
            Log::error('EXCEPCIÓN en validación rutas.store', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace'=> $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error interno durante la validación',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

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
