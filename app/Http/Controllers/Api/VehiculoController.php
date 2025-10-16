<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/vehiculos",
     * summary="Listar vehículos por perfil",
     * description="Devuelve una lista paginada de vehículos asociados a un perfil específico.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="UUID del perfil para filtrar los vehículos.",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Listado de vehículos.",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Vehiculo"))
     * )
     * )
     */
    public function index(Request $request)
    {
        $request->validate(['perfil_id' => 'required|uuid|exists:perfiles,id']);
        return Vehiculo::where('perfil_id', $request->query('perfil_id'))->paginate();
    }

    /**
     * @OA\Post(
     * path="/api/vehiculos",
     * summary="Crear un nuevo vehículo",
     * tags={"Vehiculos"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"placa", "perfil_id"},
     * @OA\Property(property="placa", type="string", example="XYZ-789"),
     * @OA\Property(property="marca", type="string", example="Ford"),
     * @OA\Property(property="modelo", type="string", example="2023"),
     * @OA\Property(property="activo", type="boolean", example=true),
     * @OA\Property(property="perfil_id", type="string", format="uuid", description="ID del perfil propietario.")
     * )
     * ),
     * @OA\Response(response=201, description="Vehículo creado.", @OA\JsonContent(ref="#/components/schemas/Vehiculo")),
     * @OA\Response(response=422, description="Error de validación.")
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'placa' => 'required|string|max:255|unique:vehiculos,placa',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'perfil_id' => 'required|uuid|exists:perfiles,id',
        ]);
        $vehiculo = Vehiculo::create($validatedData);
        return response()->json($vehiculo, 201);
    }

    /**
     * @OA\Get(
     * path="/api/vehiculos/{id}",
     * summary="Obtener detalles de un vehículo",
     * tags={"Vehiculos"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     * @OA\Parameter(name="perfil_id", in="query", required=true, @OA\Schema(type="string", format="uuid")),
     * @OA\Response(response=200, description="Detalles del vehículo.", @OA\JsonContent(ref="#/components/schemas/Vehiculo")),
     * @OA\Response(response=403, description="Acceso no autorizado."),
     * @OA\Response(response=404, description="Vehículo no encontrado.")
     * )
     */
    public function show(Vehiculo $vehiculo, Request $request)
    {
        $request->validate(['perfil_id' => 'required|uuid|exists:perfiles,id']);
        if ($vehiculo->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado para ver este vehículo.'], 403);
        }
        return response()->json($vehiculo);
    }

    /**
     * @OA\Put(
     * path="/api/vehiculos/{id}",
     * summary="Actualizar un vehículo",
     * tags={"Vehiculos"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"perfil_id"},
     * @OA\Property(property="placa", type="string"),
     * @OA\Property(property="marca", type="string"),
     * @OA\Property(property="modelo", type="string"),
     * @OA\Property(property="activo", type="boolean"),
     * @OA\Property(property="perfil_id", type="string", format="uuid", description="ID del perfil para autorización.")
     * )
     * ),
     * @OA\Response(response=200, description="Vehículo actualizado.", @OA\JsonContent(ref="#/components/schemas/Vehiculo")),
     * @OA\Response(response=403, description="Acceso no autorizado."),
     * @OA\Response(response=404, description="Vehículo no encontrado.")
     * )
     */
    public function update(Request $request, Vehiculo $vehiculo)
    {
        $validatedData = $request->validate([
            'placa' => 'string|max:255|unique:vehiculos,placa,' . $vehiculo->id,
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'perfil_id' => 'required|uuid|exists:perfiles,id',
        ]);

        if ($vehiculo->perfil_id !== $validatedData['perfil_id']) {
            return response()->json(['error' => 'No autorizado para modificar este vehículo.'], 403);
        }

        $vehiculo->update($request->except('perfil_id'));
        return response()->json($vehiculo);
    }

    /**
     * @OA\Delete(
     * path="/api/vehiculos/{id}",
     * summary="Eliminar un vehículo",
     * tags={"Vehiculos"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     * @OA\Parameter(name="perfil_id", in="query", required=true, @OA\Schema(type="string", format="uuid")),
     * @OA\Response(response=204, description="Vehículo eliminado."),
     * @OA\Response(response=403, description="Acceso no autorizado."),
     * @OA\Response(response=404, description="Vehículo no encontrado.")
     * )
     */
    public function destroy(Vehiculo $vehiculo, Request $request)
    {
        $request->validate(['perfil_id' => 'required|uuid|exists:perfiles,id']);
        if ($vehiculo->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado para eliminar este vehículo.'], 403);
        }
        $vehiculo->delete();
        return response()->json(null, 204);
    }
}
