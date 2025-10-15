<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RutaController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'perfil_id' => 'required|uuid|exists:perfiles,id',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $q = Ruta::query()
            ->select('id', 'perfil_id', 'nombre_ruta', 'color_hex')
            ->where('perfil_id', $validated['perfil_id'])
            ->orderBy('nombre_ruta');

        return response()->json($q->paginate($perPage));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'perfil_id'     => 'required|uuid|exists:perfiles,id',
            'nombre_ruta'   => 'required|string|max:150',
            'color_hex'     => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'shape_geojson' => 'nullable|string',
        ]);

        $ruta = Ruta::create([
            'perfil_id'   => $data['perfil_id'],
            'nombre_ruta' => $data['nombre_ruta'],
            'color_hex'   => $data['color_hex'] ?? null,
        ]);

        // Guardar geometría si llega GeoJSON (PostGIS: MULTILINESTRING, SRID 4326)
        if (!empty($data['shape_geojson'])) {
            DB::update(
                "UPDATE rutas SET shape = ST_Force2D(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)) WHERE id = ?",
                [$data['shape_geojson'], $ruta->id]
            );
        }

        return response()->json(
            $ruta->only(['id','perfil_id','nombre_ruta','color_hex']),
            Response::HTTP_CREATED
        );
    }

    public function show(string $id)
    {
        $ruta = Ruta::query()
            ->select('id','perfil_id','nombre_ruta','color_hex')
            ->find($id);

        if (!$ruta) {
            return response()->json(['message' => 'Ruta no encontrada'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($ruta);
    }

}
