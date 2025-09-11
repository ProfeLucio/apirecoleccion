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
        // 1. Validamos que el frontend nos envíe el perfil
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        // --- 2. LA CORRECCIÓN CLAVE ESTÁ AQUÍ ---
        $rutas = Ruta::where('perfil_id', $request->query('perfil_id'))
            ->select(
                'id',
                'nombre_ruta',
                'perfil_id',
                'color_hex',
                // Usamos ST_AsGeoJSON para convertir la geometría al formato correcto
                DB::raw('ST_AsGeoJSON(shape) as shape')
            )
            ->get();

        // 3. Devolvemos los datos
        return response()->json(['data' => $rutas]);
    }

    /**
     * @OA\Post(
     *     path="/api/rutas",
     *     summary="Crear una nueva ruta",
     *     description="Crea una ruta asociada a un perfil y calles seleccionadas.",
     *     tags={"Rutas"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre_ruta","calles","perfil_id"},
     *             @OA\Property(property="nombre_ruta", type="string", example="Ruta Norte"),
     *             @OA\Property(property="calles", type="array", @OA\Items(type="string", format="uuid")),
     *             @OA\Property(property="perfil_id", type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ruta creada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Ruta")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="El campo nombre_ruta es obligatorio.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // 1. Validamos todos los datos que llegan, incluyendo el perfil_id
        $validatedData = $request->validate([
            'nombre_ruta' => 'required|string|max:255',
            'calles' => 'required|array',
            'calles.*' => 'uuid|exists:calles,id',
            'perfil_id' => 'required|uuid|exists:perfiles,id' // Es crucial
        ]);

        // 2. Obtenemos las calles y sus geometrías
        $callesSeleccionadas = Calle::whereIn('id', $validatedData['calles'])->get();
        $shapes = $callesSeleccionadas->pluck('shape')->toArray();

        // 3. Unimos las geometrías (usando ST_Collect para más flexibilidad)
        $geometriaUnida = DB::select("SELECT ST_AsText(ST_Collect(ARRAY['" . implode("','", $shapes) . "']::geometry[])) as merged_shape")[0]->merged_shape;

        // --- 4. LA CORRECCIÓN CLAVE ESTÁ AQUÍ ---
        // Creamos la ruta usando DIRECTAMENTE los datos validados,
        // que ya incluyen nombre_ruta y perfil_id.
        $ruta = Ruta::create([
            'nombre_ruta' => $validatedData['nombre_ruta'],
            'perfil_id'   => $validatedData['perfil_id'], // Pasamos el perfil_id validado
            'shape'       => DB::raw("ST_GeomFromText('$geometriaUnida', 4326)"),
            // 'color_hex' es opcional, así que no lo incluimos si no viene
        ]);

        // 5. Adjuntamos las calles en la tabla pivote (esto no cambia)
        $orden = 0;
        foreach ($validatedData['calles'] as $calleId) {
            $ruta->calles()->attach($calleId, ['orden' => $orden++]);
        }

        // 6. Devolvemos la ruta creada
        return response()->json($ruta->load('calles'), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/rutas/{id}",
     *     summary="Obtener detalles de una ruta",
     *     description="Devuelve los detalles de una ruta, incluyendo geometría, horarios y calles.",
     *     tags={"Rutas"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la ruta",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la ruta",
     *         @OA\JsonContent(ref="#/components/schemas/Ruta")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ruta no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Ruta no encontrada")
     *         )
     *     )
     * )
     */
    public function show(Ruta $ruta)
    {
        $rutaConGeometria = Ruta::select('id', 'nombre_ruta', 'color_hex', DB::raw('ST_AsGeoJSON(shape) as shape'))
            ->where('id', $ruta->id)
            ->with('horarios', 'calles') // Carga detalles de horarios y calles
            ->firstOrFail();

        return $rutaConGeometria;
    }
}
