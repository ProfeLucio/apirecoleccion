<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recorrido;
use App\Models\Posicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecorridoController extends Controller
{
     /**
     * @OA\Post(
     * path="/api/recorridos/{recorrido_id}/posiciones",
     * summary="Registra una nueva posición GPS para un recorrido",
     * tags={"Recorridos"},
     * @OA\Parameter(
     * name="recorrido_id",
     * in="path",
     * required=true,
     * description="ID del recorrido al que se añade la posición.",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Datos de la nueva posición GPS.",
     * @OA\JsonContent(
     * required={"latitud", "longitud"},
     * @OA\Schema(
     * type="object",
     * @OA\Property(property="latitud", type="number", format="float", example=3.890),
     * @OA\Property(property="longitud", type="number", format="float", example=-77.060)
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Posición registrada exitosamente."
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (ej. faltan coordenadas)."
     * )
     * )
     */
    public function iniciarRecorrido(Request $request)
    {
        $request->validate([
            'ruta_id' => 'required|uuid|exists:rutas,id',
            'vehiculo_id' => 'required|uuid|exists:vehiculos,id',
        ]);

        // Aquí iría la lógica de autenticación para obtener el ID del conductor
        $conductorId = auth()->id();

        $recorrido = Recorrido::create([
            'ruta_id' => $request->ruta_id,
            'vehiculo_id' => $request->vehiculo_id,
            'conductor_id' => $conductorId,
            'ts_inicio' => now(),
            'estado' => 'En Curso',
        ]);

        return response()->json($recorrido, 201);
    }


    public function registrarPosicion(Request $request, Recorrido $recorrido)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $posicion = $recorrido->posiciones()->create([
            'capturado_ts' => now(),
            'geom' => DB::raw("ST_SetSRID(ST_MakePoint({$request->lon}, {$request->lat}), 4326)"),
        ]);

        return response()->json($posicion, 201);
    }


    public function finalizarRecorrido(Recorrido $recorrido)
    {
        $recorrido->update([
            'ts_fin' => now(),
            'estado' => 'Completado',
        ]);

        return response()->json($recorrido);
    }
}
