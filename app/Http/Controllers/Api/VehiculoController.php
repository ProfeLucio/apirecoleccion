<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/vehiculos",
     *     summary="Listar todos los vehículos",
     *     description="Devuelve una lista paginada de todos los vehículos de recolección.",
     *     tags={"Vehiculos"},
     *     @OA\Response(
     *         response=200,
     *         description="Listado de vehículos",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Vehiculo"))
     *     )
     * )
     */
    public function index()
    {
        return Vehiculo::paginate();
    }

    /**
     * @OA\Post(
     *     path="/api/vehiculos",
     *     summary="Crear un nuevo vehículo",
     *     description="Crea un nuevo vehículo de recolección.",
     *     tags={"Vehiculos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"placa"},
     *             @OA\Property(property="placa", type="string", example="ABC123"),
     *             @OA\Property(property="marca", type="string", example="Chevrolet"),
     *             @OA\Property(property="modelo", type="string", example="2020"),
     *             @OA\Property(property="activo", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehículo creado",
     *         @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="La placa ya existe."))
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'placa' => 'required|string|unique:vehiculos,placa|max:10',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        $vehiculo = Vehiculo::create($request->all());

        return response()->json($vehiculo, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/vehiculos/{id}",
     *     summary="Obtener detalles de un vehículo",
     *     description="Devuelve los detalles de un vehículo específico.",
     *     tags={"Vehiculos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del vehículo",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del vehículo",
     *         @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehículo no encontrado",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Vehículo no encontrado"))
     *     )
     * )
     */
    public function show(Vehiculo $vehiculo)
    {
        return $vehiculo;
    }

    /**
     * @OA\Put(
     *     path="/api/vehiculos/{id}",
     *     summary="Actualizar un vehículo",
     *     description="Actualiza los datos de un vehículo existente.",
     *     tags={"Vehiculos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del vehículo",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="placa", type="string", example="ABC123"),
     *             @OA\Property(property="marca", type="string", example="Chevrolet"),
     *             @OA\Property(property="modelo", type="string", example="2020"),
     *             @OA\Property(property="activo", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehículo actualizado",
     *         @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="La placa ya existe."))
     *     )
     * )
     */
    public function update(Request $request, Vehiculo $vehiculo)
    {
        $request->validate([
            'placa' => 'string|unique:vehiculos,placa,' . $vehiculo->id . '|max:10',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        $vehiculo->update($request->all());

        return response()->json($vehiculo);
    }

    /**
     * @OA\Delete(
     *     path="/api/vehiculos/{id}",
     *     summary="Eliminar un vehículo",
     *     description="Elimina un vehículo de la base de datos.",
     *     tags={"Vehiculos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del vehículo",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Vehículo eliminado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehículo no encontrado",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Vehículo no encontrado"))
     *     )
     * )
     */
    public function destroy(Vehiculo $vehiculo)
    {
        $vehiculo->delete();

        return response()->json(null, 204);
    }
}
