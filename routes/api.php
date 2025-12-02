<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importamos todos los controladores que hemos creado
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\CalleController;
use App\Http\Controllers\Api\RutaController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\RecorridoController;
use App\Http\Controllers\Api\PosicionController;
use App\Http\Controllers\Api\PerfilController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Ruta por defecto de Laravel para obtener el usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// --- GESTIÓN DE DATOS MAESTROS (Normalmente para Administradores) ---

// CRUD para Vehículos
Route::apiResource('vehiculos', VehiculoController::class);

// Consulta de Calles (solo lectura, ya que se importan)
Route::apiResource('calles', CalleController::class)->only(['index', 'show']);

// CRUD para Rutas Lógicas (creadas por el admin)
Route::get('rutas/todas', [RutaController::class, 'getAll']);
Route::apiResource('rutas', RutaController::class)->only(['index', 'show', 'store']);

// CRUD para Horarios (anidado dentro de las rutas)
//Route::apiResource('rutas.horarios', HorarioController::class)->shallow();


// --- OPERACIONES EN TIEMPO REAL (Normalmente para Conductores) ---

// Iniciar y finalizar un recorrido
Route::post('/recorridos/iniciar', [RecorridoController::class, 'iniciarRecorrido']);
Route::post('/recorridos/{recorrido}/finalizar', [RecorridoController::class, 'finalizarRecorrido']);
Route::get('/misrecorridos', [RecorridoController::class, 'index']);

// Historial y registro de posiciones (anidado dentro de los recorridos)
Route::get('recorridos/rutas/{ruta_id}', [RecorridoController::class, 'historialPorRuta']);
Route::apiResource('recorridos.posiciones', PosicionController::class)->only(['index', 'store']);
Route::get('perfiles/todas', [PerfilController::class, 'getAll']);
Route::apiResource('perfiles', PerfilController::class)->only(['index']);
