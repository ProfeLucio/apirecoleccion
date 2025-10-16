<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use App\Models\Ruta;
use Illuminate\Http\Request;

class HorarioController extends Controller
{

    public function index(Ruta $ruta)
    {
        return $ruta->horarios;
    }

    public function store(Request $request, Ruta $ruta)
    {
        $request->validate([
            'dia_semana' => 'required|integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s|after:hora_inicio',
        ]);

        $horario = $ruta->horarios()->create($request->all());

        return response()->json($horario, 201);
    }

    public function show(Horario $horario)
    {
        return $horario;
    }

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

    public function destroy(Horario $horario)
    {
        $horario->delete();

        return response()->json(null, 204);
    }
}
