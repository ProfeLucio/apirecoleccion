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
 * description="Crea una ruta a partir de una geometría GeoJSON. La geometría puede ser la unión de calles existentes o un trazado personalizado.",
 * tags={"Rutas"},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"nombre_ruta", "perfil_id", "shape"},
 * @OA\Property(property="nombre_ruta", type="string", example="Ruta Personalizada 1"),
 * @OA\Property(property="perfil_id", type="string", format="uuid"),
 * @OA\Property(
 * property="shape",
 * type="object",
 * description="Objeto GeoJSON que representa la geometría de la ruta (ej. LineString o MultiLineString)."
 * ),
 * @OA\Property(
 * property="calles_ids",
 * type="array",
 * description="(Opcional) Lista de UUIDs de las calles que componen esta ruta, para referencia.",
 * @OA\Items(type="string", format="uuid")
 * )
 * )
 * ),
 * @OA\Response(response=201, description="Ruta creada"),
 * @OA\Response(response=422, description="Validación fallida")
 * )
 */
public function store(Request $request)
{
    $validatedData = $request->validate([
        'nombre_ruta' => 'required|string|max:255',
        'perfil_id'   => 'required|uuid|exists:perfiles,id',
        'shape'       => 'required|json', // Recibimos la geometría como un string JSON
        'calles_ids'  => 'nullable|array'
    ]);

    $ruta = null;

    DB::transaction(function () use ($validatedData, &$ruta) {

        // 1. Convertimos el GeoJSON a una geometría de PostGIS y creamos la ruta
        $ruta = Ruta::create([
            'nombre_ruta' => $validatedData['nombre_ruta'],
            'perfil_id'   => $validatedData['perfil_id'],
            'shape'       => DB::raw("ST_GeomFromGeoJSON('{$validatedData['shape']}')")
        ]);

        // en la tabla pivote solo como referencia.
        if (!empty($validatedData['calles_ids'])) {
            $orden = 0;
            foreach ($validatedData['calles_ids'] as $calleId) {
                // Asumimos que la relación 'calles' y la tabla pivote 'ruta_calle' aún existen
                $ruta->calles()->attach($calleId, ['orden' => $orden++]);
            }
        }
    });

    // Devolvemos la ruta creada. El accesor en el modelo se encargará de convertir
    // el 'shape' de vuelta a GeoJSON.
    return response()->json($ruta, 201);
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
