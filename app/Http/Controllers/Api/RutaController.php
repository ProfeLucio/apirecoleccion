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

    public function store(Request $request)
    {
        // Aceptar shape como array o string
        $validated = $request->validate([
            'nombre_ruta' => 'required|string|max:255',
            'perfil_id'   => 'required|uuid|exists:perfiles,id',
            'shape'       => 'required_without:calles_ids|nullable',   // <- ya no string|json
            'calles_ids'  => 'required_without:shape|nullable|array|min:1',
            'calles_ids.*'=> 'uuid|exists:calles,id',
        ]);

        /*
        try {
            $rutaId = null;

            DB::transaction(function () use ($validated, &$rutaId) {

                $ruta = Ruta::create([
                    'nombre_ruta' => $validated['nombre_ruta'],
                    'perfil_id'   => $validated['perfil_id'],
                    // ¡NO poner 'shape' aquí!
                ]);

                // ----- Caso A: viene shape (objeto o string) -----
                if (!empty($validated['shape'])) {
                    // Normalizar a string JSON
                    if (is_array($validated['shape'])) {
                        $geojson = json_encode($validated['shape'], JSON_UNESCAPED_SLASHES);
                    } else {
                        $geojson = (string) $validated['shape'];
                    }

                    // Actualizar shape con bindings seguros
                    DB::update(
                        "UPDATE rutas
                        SET shape = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)
                        WHERE id = ?",
                        [$geojson, $ruta->id]
                    );
                }

                // ----- Caso B: viene calles_ids (unión) -----
                elseif (!empty($validated['calles_ids'])) {
                    $merged = DB::table('calles')
                        ->whereIn('id', $validated['calles_ids'])
                        ->select(DB::raw('ST_AsText(ST_Union(shape)) AS merged_shape'))
                        ->first();

                    if (!$merged || $merged->merged_shape === null) {
                        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'No se pudo generar una geometría válida a partir de las calles seleccionadas.');
                    }

                    DB::update(
                        "UPDATE rutas
                        SET shape = ST_SetSRID(ST_GeomFromText(?, 4326), 4326)
                        WHERE id = ?",
                        [$merged->merged_shape, $ruta->id]
                    );

                    // guardar orden en pivote si aplica
                    $callesToAttach = [];
                    $orden = 0;
                    foreach ($validated['calles_ids'] as $calleId) {
                        $callesToAttach[$calleId] = ['orden' => $orden++];
                    }
                    $ruta->calles()->attach($callesToAttach);
                }

                $rutaId = $ruta->id;
            });

            // Responder con GeoJSON calculado (evitar serializar geometry crudo)
            $ruta = Ruta::select('id','perfil_id','nombre_ruta','color_hex')
                ->addSelect(DB::raw('ST_AsGeoJSON(shape) AS shape_geojson'))
                ->with('calles')
                ->findOrFail($rutaId);

            return response()->json($ruta, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('Error creando ruta', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'No se pudo crear la ruta.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
        */
        return response()->json(['message' => 'Creación de rutas deshabilitada temporalmente.', 'data' => $request->all()], 200);

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
