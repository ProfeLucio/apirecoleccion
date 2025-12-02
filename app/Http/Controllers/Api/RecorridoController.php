<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recorrido;
use Illuminate\Http\Request;

class RecorridoController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/misrecorridos",
     * summary="Listar recorridos por perfil",
     * tags={"Recorridos"},
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="UUID del perfil para filtrar los recorridos.",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(response=200, description="Listado de recorridos."),
     * @OA\Response(response=422, description="Error de validación.")
     * )
     */
    public function index(Request $request)
    {
        $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        $recorridos = Recorrido::where('perfil_id', $request->query('perfil_id'))
            ->orderBy('ts_inicio', 'desc')
            ->get();

        return response()->json(['data' => $recorridos]);
    }

    public function historialPorRuta(Request $request, $ruta_id) // <--- Agrega $ruta_id aquí
    {

        // Usamos directamente la variable $ruta_id que entra por la URL
        /*
        $recorridos = Recorrido::where('ruta_id', $ruta_id)
            ->orderBy('ts_inicio', 'desc')
            ->get();*/
        $recorridos = Recorrido::all();

        return response()->json(['data' => $recorridos]);
    }

    /**
     * @OA\Post(
     * path="/api/recorridos/iniciar",
     * summary="Iniciar un nuevo recorrido",
     * description="Crea un nuevo registro de recorrido asociado a un perfil.",
     * tags={"Recorridos"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"ruta_id", "vehiculo_id", "perfil_id"},
     * @OA\Property(property="ruta_id", type="string", format="uuid"),
     * @OA\Property(property="vehiculo_id", type="string", format="uuid"),
     * @OA\Property(property="perfil_id", type="string", format="uuid")
     * )
     * ),
     * @OA\Response(response=201, description="Recorrido iniciado exitosamente."),
     * @OA\Response(response=422, description="Datos de validación inválidos.")
     * )
     */
    public function iniciarRecorrido(Request $request)
    {
        $validatedData = $request->validate([
            'ruta_id'     => 'required|uuid|exists:rutas,id',
            'vehiculo_id' => 'required|uuid|exists:vehiculos,id',
            'perfil_id'   => 'required|uuid|exists:perfiles,id',
        ]);

        // --- MEJORA: Validación de recorrido activo ---
        $recorridoExistente = Recorrido::where('vehiculo_id', $validatedData['vehiculo_id'])
                                        ->where('estado', 'En Curso')
                                        ->first();

        if ($recorridoExistente) {
            return response()->json([
                'error' => 'El vehículo ya tiene un recorrido en curso.'
            ], 409); // 409 Conflict es un buen código de estado para esto
        }
        // --- Fin de la mejora ---

        $recorrido = Recorrido::create([
            'ruta_id'     => $validatedData['ruta_id'],
            'vehiculo_id' => $validatedData['vehiculo_id'],
            'perfil_id'   => $validatedData['perfil_id'],
            'ts_inicio'   => now(),
            'estado'      => 'En Curso',
        ]);

        return response()->json($recorrido, 201);
    }

    /**
     * @OA\Post(
     * path="/api/recorridos/{recorrido}/finalizar",
     * summary="Finalizar un recorrido existente",
     * tags={"Recorridos"},
     * @OA\Parameter(
     * name="recorrido",
     * in="path",
     * required=true,
     * description="ID del recorrido a finalizar.",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"perfil_id"},
     * @OA\Property(property="perfil_id", type="string", format="uuid", description="ID del perfil propietario del recorrido.")
     * )
     * ),
     * @OA\Response(response=200, description="Recorrido finalizado."),
     * @OA\Response(response=403, description="Acción no autorizada. El perfil no corresponde."),
     * @OA\Response(response=404, description="Recorrido no encontrado.")
     * )
     */
    public function finalizarRecorrido(Request $request, Recorrido $recorrido)
    {
        $validatedData = $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id'
        ]);

        // --- ¡Verificación de seguridad clave! ---
        if ($recorrido->perfil_id !== $validatedData['perfil_id']) {
            return response()->json(['error' => 'No autorizado para modificar este recorrido.'], 403);
        }

        $recorrido->update([
            'ts_fin' => now(),
            'estado' => 'Completado',
        ]);

        return response()->json($recorrido);
    }
}
