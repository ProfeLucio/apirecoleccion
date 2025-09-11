<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use App\Models\Ruta; // Necesitamos el modelo Ruta para anidar la creación
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/horarios",
     * summary="Listar todos los horarios por perfil",
     * tags={"Horarios"},
     * @OA\Parameter(
     * name="perfil_id",
     * in="query",
     * required=true,
     * description="El UUID del perfil para filtrar los horarios.",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Horario")
     * )
     * )
     * )
     */
    public function index(Ruta $ruta)
    {
        return $ruta->horarios;
    }

    /**
     * @OA\Post(
     *     path="/api/rutas/{ruta}/horarios",
     *     summary="Crear un nuevo horario para una ruta",
     *     description="Crea un nuevo horario asociado a una ruta específica.",
     *     tags={"Horarios"},
     *     @OA\Parameter(
     *         name="ruta",
     *         in="path",
     *         required=true,
     *         description="ID de la ruta",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dia_semana","hora_inicio"},
     *             @OA\Property(property="dia_semana", type="integer", example=1),
     *             @OA\Property(property="hora_inicio", type="string", format="time", example="06:00:00"),
     *             @OA\Property(property="hora_fin", type="string", format="time", example="08:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Horario creado",
     *         @OA\JsonContent(ref="#/components/schemas/Horario")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="El campo dia_semana es obligatorio."))
     *     )
     * )
     */
    public function store(Request $request, Ruta $ruta)
    {
        $request->validate([
            // dia_semana: 1=Lunes, 2=Martes, ..., 7=Domingo
            'dia_semana' => 'required|integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s|after:hora_inicio',
        ]);

        $horario = $ruta->horarios()->create($request->all());

        return response()->json($horario, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/horarios/{id}",
     *     summary="Obtener detalles de un horario",
     *     description="Devuelve los detalles de un horario específico.",
     *     tags={"Horarios"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del horario",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del horario",
     *         @OA\JsonContent(ref="#/components/schemas/Horario")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Horario no encontrado",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Horario no encontrado"))
     *     )
     * )
     */
    public function show(Horario $horario)
    {
        return $horario;
    }

    /**
     * @OA\Put(
     *     path="/api/horarios/{id}",
     *     summary="Actualizar un horario",
     *     description="Actualiza los datos de un horario existente.",
     *     tags={"Horarios"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del horario",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="dia_semana", type="integer", example=1),
     *             @OA\Property(property="hora_inicio", type="string", format="time", example="06:00:00"),
     *             @OA\Property(property="hora_fin", type="string", format="time", example="08:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Horario actualizado",
     *         @OA\JsonContent(ref="#/components/schemas/Horario")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="El campo hora_inicio es inválido."))
     *     )
     * )
     */
    public function update(Request $request, Horario $horario)
    {
        $request->validate([
            'dia_semana' => 'integer|between:1,7',
            'hora_inicio' => 'date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s|after:hora_inicio',
        ]);

        $horario->update($request->all());

        return response()->json($horario);
    }

    /**
     * @OA\Delete(
     *     path="/api/horarios/{id}",
     *     summary="Eliminar un horario",
     *     description="Elimina un horario de la base de datos.",
     *     tags={"Horarios"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del horario",
     *         @OA\Schema(type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-1234567890ab")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Horario eliminado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Horario no encontrado",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Horario no encontrado"))
     *     )
     * )
     */
    public function destroy(Horario $horario)
    {
        $horario->delete();

        return response()->json(null, 204);
    }
}
