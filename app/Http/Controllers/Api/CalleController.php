<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalleController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/calles",
     * summary="Listar todas las calles",
     * description="Devuelve una lista de todas las calles disponibles en el sistema. Es un recurso global y no se filtra por perfil.",
     * tags={"Calles"},
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(
     * property="data",
     * type="array",
     * @OA\Items(ref="#/components/schemas/Calle")
     * )
     * )
     * )
     * )
     */
    public function index()
    {
        // 1. Obtenemos TODAS las calles con get()
        $calles = Calle::select(
            'id',
            'nombre',
            DB::raw('ST_AsGeoJSON(shape) as shape')
        )->get();

        // 2. Envolvemos la colección en un array con la clave "data"
        return response()->json([
            'data' => $calles
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/calles/{id}",
     *     summary="Obtener detalles de una calle",
     *     description="Devuelve los detalles de una calle específica, incluyendo su geometría.",
     *     tags={"Calles"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la calle",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la calle",
     *         @OA\JsonContent(ref="#/components/schemas/Calle")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Calle no encontrada",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Calle no encontrada"))
     *     )
     * )
     */
    public function show(Calle $calle)
    {
        // Buscamos de nuevo el registro para poder aplicar la conversión ST_AsGeoJSON.
        $calleConGeometria = Calle::select(
            'id',
            'nombre',
            DB::raw('ST_AsGeoJSON(shape) as shape')
        )->where('id', $calle->id)->firstOrFail();

        return $calleConGeometria;
    }

    // Dejamos los otros métodos vacíos porque las calles se gestionan
    // a través del comando de importación, no directamente desde la API.
}
