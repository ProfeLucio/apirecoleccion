<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\Calle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

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
        // 1. Validar los datos
        /*
        $validated = $request->validate([
            'nombre_ruta' => 'required|string|max:255',
            'perfil_id'   => 'required|uuid|exists:perfiles,id',
            'shape'       => 'required_without:calles_ids|nullable|string|json',
            'calles_ids'  => 'required_without:shape|nullable|array|min:1',
            'calles_ids.*'=> 'uuid|exists:calles,id',
        ]);
*/
       // $shapeExpr = null;
        //$callesToAttach = [];

        // ==========================================================
        // 2. CALCULAR LA GEOMETRÍA (shapeExpr) FUERA DE LA CREACIÓN
        // ==========================================================

        // Caso A: GeoJSON directo (string)
        /*
        if (!empty($validated['shape'])) {
            $geojson = (string) $validated['shape'];
            // Usamos la interpolación directa que te funciona, asumiendo que el JSON fue validado.
            $shapeExpr = DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$geojson}'), 4326)");
        }*/
        // Caso B: unión de calles
        /*
        elseif (!empty($validated['calles_ids'])) {
            $merged = DB::table('calles')
                ->whereIn('id', $validated['calles_ids'])
                ->select(DB::raw('ST_AsText(ST_Union(shape)) AS merged_shape'))
                ->first();

            if (!$merged || $merged->merged_shape === null) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'No se pudo generar una geometría válida a partir de las calles seleccionadas.');
            }

            $wkt = $merged->merged_shape;
            // Usamos la interpolación directa para ST_GeomFromText
            $shapeExpr = DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}', 4326), 4326)");

            // Preparamos el array de calles para adjuntar (ya que estamos en este caso)
            $orden = 0;
            foreach ($validated['calles_ids'] as $calleId) {
                $callesToAttach[$calleId] = ['orden' => $orden++];
            }
        }
        */

        // Verificación final de que se pudo calcular la geometría
        /*
        if ($shapeExpr === null) {
            // Esto solo debería ocurrir si la validación falla de forma inesperada.
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'La geometría de la ruta no fue procesada. Verifique los datos de entrada.');
        }*/

        // ==========================================================
        // 3. CREACIÓN Y ADJUNCIÓN (DENTRO DE LA TRANSACCIÓN)
        // ==========================================================

/*
            $rutaId = null;

            DB::transaction(function () use ($validated, $shapeExpr, $callesToAttach, &$rutaId) {

                // Crear la ruta, incluyendo el SHAPE CALCULADO
                $ruta = Ruta::create([
                    'nombre_ruta' => $validated['nombre_ruta'],
                    'perfil_id'   => $validated['perfil_id'],
                    'shape'       => $shapeExpr, // AHORA shape ya tiene un valor
                ]);

                // Adjuntar calles si se calcularon
                if (!empty($callesToAttach)) {
                    $ruta->calles()->attach($callesToAttach);
                }

                $rutaId = $ruta->id;
            });

            // 4. Responder con GeoJSON calculado (fuera de la transacción)
            $ruta = Ruta::select('id','perfil_id','nombre_ruta','color_hex')
                ->addSelect(DB::raw('ST_AsGeoJSON(shape) AS shape_geojson'))
                ->with('calles')
                ->findOrFail($rutaId);
*/
            //return response()->json($ruta, Response::HTTP_CREATED);

            return response()->json(['data' => 'Funciona'], 201);


        // Línea de código muerta al final del método, puede ser eliminada
        // return response()->json(['message' => 'Creación de rutas deshabilitada temporalmente.', 'data' => $request->all()], 200);

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
