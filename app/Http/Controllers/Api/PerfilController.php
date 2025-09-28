<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perfil;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/perfiles",
     * summary="Listar todos los perfiles (Deshabilitado)",
     * tags={"Perfiles"},
     * description="Este endpoint ha sido deshabilitado por seguridad para evitar la exposición de los UUIDs de los perfiles.",
     * @OA\Response(
     * response=403,
     * description="Acceso denegado"
     * )
     * )
     */
    public function index()
    {
        // Se devuelve una respuesta 403 Forbidden para indicar que este recurso no es público.
        // Esto previene que los equipos puedan ver los UUIDs de otros.
        return response()->json(['error' => 'No autorizado para listar perfiles.'], 403);
    }
}
