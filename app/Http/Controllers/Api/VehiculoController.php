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
     * summary="Listar todos los vehículos de un perfil",
     * description="Devuelve una lista paginada de todos los vehículos de recolección pertenecientes a un perfil específico.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="ID del perfil para filtrar los vehículos",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Listado de vehículos",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Vehiculo"))
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (e.j., perfil_id no enviado o inválido)"
     * )
     * )
     */
    public function index(Request $request)
    {
        // CORRECCIÓN 1: Validar el perfil_id y usarlo para filtrar
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        return Vehiculo::where('perfil_id', $request->query('perfil_id'))->paginate();
    }

/**
     * @OA\Post(
     * path="/api/vehiculos",
     * summary="Crear un nuevo vehículo",
     * description="Crea un nuevo vehículo de recolección y lo asocia a un perfil.",
     * tags={"Vehiculos"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"placa", "perfil_id"},
     * @OA\Property(property="placa", type="string", example="ABC123"),
     * @OA\Property(property="marca", type="string", example="Chevrolet"),
     * @OA\Property(property="modelo", type="string", example="2020"),
     * @OA\Property(property="activo", type="boolean", example=true),
     * @OA\Property(property="perfil_id", type="string", format="uuid", description="ID del perfil al que pertenecerá el vehículo", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
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
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="La placa ya existe."))
     * )
     * )
     */
    public function store(Request $request)
    {
        // CORRECCIÓN 2: Añadir validación para perfil_id
        $validatedData = $request->validate([
            'placa'     => 'required|string|unique:vehiculos|max:10',
            'marca'     => 'nullable|string|max:255',
            'modelo'    => 'nullable|string|max:255',
            'activo'    => 'boolean',
            'perfil_id' => 'required|uuid|exists:perfiles,id' // <-- Se añade
        ]);

        $vehiculo = Vehiculo::create($validatedData);

        return response()->json($vehiculo, 201);
    }

    /**
     * @OA\Get(
     * path="/api/vehiculos/{id}",
     * summary="Obtener detalles de un vehículo",
     * description="Devuelve los detalles de un vehículo específico. Requiere el perfil del propietario para autorización.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID del vehículo",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="ID del perfil propietario para validar la autorización",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Detalles del vehículo",
     * @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     * ),
     * @OA\Response(
     * response=403,
     * description="Acceso no autorizado",
     * @OA\JsonContent(@OA\Property(property="error", type="string", example="No autorizado para ver este vehículo."))
     * ),
     * @OA\Response(
     * response=404,
     * description="Vehiculo no encontrado"
     * )
     * )
     */
    public function show(Vehiculo $vehiculo, Request $request)
    {
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        // Verificación de seguridad: Asegura que el vehículo pertenece al perfil que lo solicita.
        if ($vehiculo->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado para ver este vehículo.'], 403);
        }

        return response()->json($vehiculo);
    }

/**
     * @OA\Put(
     * path="/api/vehiculos/{id}",
     * summary="Actualizar un vehículo",
     * description="Actualiza los datos de un vehículo existente. Requiere el ID del perfil para autorización.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID del vehículo a actualizar",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"perfil_id"},
     * @OA\Property(property="placa", type="string", example="ABC123"),
     * @OA\Property(property="marca", type="string", example="Chevrolet"),
     * @OA\Property(property="modelo", type="string", example="2020"),
     * @OA\Property(property="activo", type="boolean", example=true),
     * @OA\Property(property="perfil_id", type="string", format="uuid", description="ID del perfil propietario para validar autorización", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Vehículo actualizado",
     * @OA\JsonContent(ref="#/components/schemas/Vehiculo")
     * ),
     * @OA\Response(
     * response=403,
     * description="Acceso no autorizado",
     * @OA\JsonContent(@OA\Property(property="error", type="string", example="No autorizado para modificar vehículos de otro perfil."))
     * ),
     * @OA\Response(
     * response=422,
     * description="Validación fallida"
     * )
     * )
     */
    public function update(Request $request, Vehiculo $vehiculo)
    {
        // El perfil_id se puede pasar en el body o se puede obtener del token,
        // asumiendo que el request body puede tener 'perfil_id' para la verificación
        $validatedAuth = $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        // CORRECCIÓN 4: Verificación de seguridad crucial antes de la actualización.
        if ($vehiculo->perfil_id !== $validatedAuth['perfil_id']) {
            return response()->json(['error' => 'No autorizado para modificar vehículos de otro perfil.'], 403);
        }

        // ... (rest of validation)

        $vehiculo->update($request->except('perfil_id')); // Evitar que se cambie el perfil_id

        return response()->json($vehiculo);
    }

   /**
     * @OA\Delete(
     * path="/api/vehiculos/{id}",
     * summary="Eliminar un vehículo",
     * description="Elimina un vehículo de la base de datos. Requiere el ID del perfil para autorización.",
     * tags={"Vehiculos"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID del vehículo a eliminar",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="ID del perfil propietario del vehículo para validar autorización",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=204,
     * description="Vehículo eliminado"
     * ),
     * @OA\Response(
     * response=403,
     * description="Acceso no autorizado",
     * @OA\JsonContent(@OA\Property(property="error", type="string", example="No autorizado para eliminar este vehículo."))
     * ),
     * @OA\Response(
     * response=404,
     * description="Vehículo no encontrado"
     * )
     * )
     */
    public function destroy(Vehiculo $vehiculo, Request $request)
    {
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        // CORRECCIÓN 5: Verificación de seguridad crucial.
        if ($vehiculo->perfil_id !== $request->query('perfil_id')) {
            return response()->json(['error' => 'No autorizado para eliminar este vehículo.'], 403);
        }

        $vehiculo->delete();

        return response()->json(null, 204);
    }
}
