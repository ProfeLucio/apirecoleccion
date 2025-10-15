<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VehiculoController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/vehiculos",
     * summary="Listar vehículos (filtrado opcional por perfil)",
     * description="Devuelve una lista paginada de todos los vehículos, opcionalmente filtrada por perfil_id. Si perfil_id no se proporciona, lista todos los vehículos.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=false,
     * description="UUID del perfil para filtrar sus vehículos",
     * @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     * ),
     * @OA\Response(
     * response=200,
     * description="Listado de vehículos",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Vehiculo"))
     * ),
     * @OA\Response(
     * response=422,
     * description="Validación fallida (si el UUID es inválido)",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="El perfil_id proporcionado es inválido."))
     * )
     * )
     */
    public function index(Request $request)
    {
        $perfilId = $request->query('perfil_id');

        if ($perfilId) {
            $request->validate([
                'perfil_id' => 'uuid|exists:perfiles,id'
            ]);
        }

        $query = Vehiculo::query();

        if ($perfilId) {
            $query->where('perfil_id', $perfilId);
        }

        return $query->paginate();
    }

    // ----------------------------------------------------------------------------------

    /**
     * @OA\Post(
     * path="/api/vehiculos",
     * summary="Crear un nuevo vehículo",
     * description="Crea un nuevo vehículo de recolección, requiere el perfil_id en el cuerpo de la petición.",
     * tags={"Vehiculos"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"placa", "perfil_id"},
     * @OA\Property(property="placa", type="string", example="ABC123"),
     * @OA\Property(property="marca", type="string", example="Chevrolet"),
     * @OA\Property(property="modelo", type="string", example="2020"),
     * @OA\Property(property="activo", type="boolean", example=true),
     * @OA\Property(property="perfil_id", type="string", format="uuid", example="e6a7b8c9-d0e1-2345-fghi-678901234567", description="ID del perfil al que pertenece el vehículo")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Vehículo creado",
     * @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     * ),
     * @OA\Response(
     * response=422,
     * description="Validación fallida",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="La placa ya existe o el perfil_id es inválido."))
     * )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'placa' => 'required|string|unique:vehiculos,placa|max:10',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'perfil_id' => 'required|uuid|exists:perfiles,id',
        ]);

        $data = $request->all();

        if (!isset($data['id'])) {
            $data['id'] = Str::uuid();
        }

        $vehiculo = Vehiculo::create($data);

        return response()->json($vehiculo, 201);
    }

    // ----------------------------------------------------------------------------------

    /**
     * @OA\Get(
     * path="/api/vehiculos/{id}",
     * summary="Obtener detalles de un vehículo",
     * description="Devuelve los detalles de un vehículo específico por su ID.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID del vehículo (UUID)",
     * @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     * ),
     * @OA\Response(
     * response=200,
     * description="Detalles del vehículo",
     * @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     * ),
     * @OA\Response(
     * response=404,
     * description="Vehículo no encontrado",
     * @OA\JsonContent(@OA\Property(property="error", type="string", example="Vehículo no encontrado"))
     * )
     * )
     */
    public function show(Vehiculo $vehiculo)
    {
        return $vehiculo;
    }

    // ----------------------------------------------------------------------------------

    /**
     * @OA\Put(
     * path="/api/vehiculos/{id}",
     * summary="Actualizar un vehículo",
     * description="Actualiza los datos de un vehículo existente usando su ID.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID del vehículo (UUID)",
     * @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     * ),
     * @OA\RequestBody(
     * required=false,
     * @OA\JsonContent(
     * @OA\Property(property="placa", type="string", example="ABC123"),
     * @OA\Property(property="marca", type="string", example="Chevrolet"),
     * @OA\Property(property="modelo", type="string", example="2020"),
     * @OA\Property(property="activo", type="boolean", example=true),
     * @OA\Property(property="perfil_id", type="string", format="uuid", example="e6a7b8c9-d0e1-2345-fghi-678901234567", description="Opcional: ID del perfil para reasignar el vehículo")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Vehículo actualizado",
     * @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     * ),
     * @OA\Response(
     * response=422,
     * description="Validación fallida",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="La placa ya existe."))
     * )
     * )
     */
    public function update(Request $request, Vehiculo $vehiculo)
    {
        $request->validate([
            'placa' => 'nullable|string|unique:vehiculos,placa,' . $vehiculo->id . ',id|max:10',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'perfil_id' => 'nullable|uuid|exists:perfiles,id',
        ]);

        $vehiculo->update($request->all());

        return response()->json($vehiculo);
    }

    // ----------------------------------------------------------------------------------

    /**
     * @OA\Delete(
     * path="/api/vehiculos/{id}",
     * summary="Eliminar un vehículo",
     * description="Elimina un vehículo de la base de datos por su ID.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID del vehículo (UUID)",
     * @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     * ),
     * @OA\Response(
     * response=204,
     * description="Vehículo eliminado"
     * ),
     * @OA\Response(
     * response=404,
     * description="Vehículo no encontrado",
     * @OA\JsonContent(@OA\Property(property="error", type="string", example="Vehículo no encontrado"))
     * )
     * )
     */
    public function destroy(Vehiculo $vehiculo)
    {
        $vehiculo->delete();

        return response()->json(null, 204);
    }
}
