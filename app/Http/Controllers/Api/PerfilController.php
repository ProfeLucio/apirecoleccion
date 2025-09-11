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
     * summary="Listar todos los perfiles",
     * tags={"Perfiles"},
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Perfil")
     * )
     * )
     * )
     */
    public function index()
    {
        // Devuelve todos los perfiles ordenados por nombre
        return Perfil::orderBy('nombre_perfil', 'asc')->get();
    }
}
