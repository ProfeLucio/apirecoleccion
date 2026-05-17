<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Posicion;
use App\Models\Recorrido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $recorridos = Recorrido::where('ruta_id', $ruta_id)
            ->orderBy('ts_inicio', 'desc')
            ->get();


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

    /**
     * @OA\Post(
     *   path="/api/recorridos/posiciones/{posicion_id}/imagen",
     *   summary="Subir imagen de una posición",
     *   description="Permite registrar o actualizar la imagen asociada a una posición específica de un recorrido. La imagen se recibe en formato Base64, se procesa para que su lado mayor no supere los 256px, se mantiene la proporción original y se almacena en formato WEBP. Solo se permite la operación si el recorrido asociado se encuentra en estado En Curso.",
     *   tags={"Recorridos"},
     *   @OA\Parameter(
     *     name="posicion_id",
     *     in="path",
     *     required=true,
     *     description="UUID de la posición a la que se asocia la imagen.",
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"imagen_base64"},
     *       @OA\Property(
     *         property="imagen_base64",
     *         type="string",
     *         description="Imagen codificada en Base64. Puede incluir el prefijo data URI (data:image/jpeg;base64,...) o ser Base64 puro.",
     *         example="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ..."
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Imagen registrada correctamente.",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Imagen registrada correctamente."),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="posicion_id", type="string", format="uuid"),
     *         @OA\Property(property="imagen", type="string", example="posiciones/uuid.webp"),
     *         @OA\Property(property="url", type="string", example="https://dominio.com/storage/posiciones/uuid.webp")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="La posición indicada no existe."),
     *   @OA\Response(response=409, description="El recorrido asociado no se encuentra en curso."),
     *   @OA\Response(response=422, description="Datos incompletos o imagen inválida."),
     *   @OA\Response(response=500, description="Error interno al procesar la imagen.")
     * )
     */
    public function subirImagenPosicion(Request $request, string $posicion_id)
    {
        // 1. Validar campo requerido
        $request->validate([
            'imagen_base64' => 'required|string',
        ]);

        // 2. Validar que el Base64 no supere 5 MB (aprox. 6.86 MB en base64)
        $rawBase64 = $request->input('imagen_base64');
        if (strlen($rawBase64) > 7 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'La imagen supera el tamaño máximo permitido de 5 MB.',
            ], 422);
        }

        // 3. Extraer la parte de datos pura (quitar prefijo data URI si existe)
        if (str_contains($rawBase64, ',')) {
            [, $base64Data] = explode(',', $rawBase64, 2);
        } else {
            $base64Data = $rawBase64;
        }

        // 4. Decodificar Base64
        // Los '+' del base64 se convierten en espacios en form-data (URL encoding),
        // hay que restaurarlos ANTES de eliminar whitespace real (saltos de línea).
        $base64Data = str_replace(' ', '+', $base64Data);
        $base64Data = preg_replace('/[\r\n\t\v\f]/', '', $base64Data);
        $imageData = base64_decode($base64Data, true);
        if ($imageData === false) {
            return response()->json([
                'success' => false,
                'message' => '[DEBUG paso 4] base64_decode falló. Longitud del string: ' . strlen($base64Data) . ' chars.',
            ], 422);
        }

        // 5. Validar que es una imagen real y de tipo permitido
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes, true)) {
            return response()->json([
                'success' => false,
                'message' => '[DEBUG paso 5] MIME detectado: ' . $mimeType . '. No está entre los permitidos.',
            ], 422);
        }

        // 6. Buscar la posición
        $posicion = Posicion::find($posicion_id);
        if (!$posicion) {
            return response()->json([
                'success' => false,
                'message' => 'La posición indicada no existe.',
            ], 404);
        }

        // 7. Verificar recorrido asociado
        $recorrido = Recorrido::find($posicion->recorrido_id);
        if (!$recorrido) {
            return response()->json([
                'success' => false,
                'message' => 'La posición no tiene un recorrido asociado válido.',
            ], 404);
        }

        if ($recorrido->estado !== 'En Curso') {
            return response()->json([
                'success' => false,
                'message' => 'No es posible registrar la imagen porque el recorrido asociado a la posición no se encuentra en curso.',
            ], 409);
        }

        // 8. Crear imagen GD desde los datos binarios
        if (!function_exists('imagecreatefromstring')) {
            return response()->json([
                'success' => false,
                'message' => '[DEBUG paso 8] La extensión GD no está instalada en PHP.',
            ], 500);
        }
        $gdImage = @imagecreatefromstring($imageData);
        if ($gdImage === false) {
            return response()->json([
                'success' => false,
                'message' => '[DEBUG paso 8] imagecreatefromstring falló. MIME: ' . $mimeType . ', bytes decodificados: ' . strlen($imageData) . ', chars base64: ' . strlen($base64Data) . ', chars raw: ' . strlen($rawBase64) . '.',
            ], 422);
        }

        try {
            // 9. Redimensionar manteniendo proporción (lado mayor máx 512 px)
            $origWidth  = imagesx($gdImage);
            $origHeight = imagesy($gdImage);
            $maxSide = 256;

            if ($origWidth > $maxSide || $origHeight > $maxSide) {
                if ($origWidth >= $origHeight) {
                    $newWidth  = $maxSide;
                    $newHeight = (int) round($origHeight * ($maxSide / $origWidth));
                } else {
                    $newHeight = $maxSide;
                    $newWidth  = (int) round($origWidth * ($maxSide / $origHeight));
                }

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                // Preservar transparencia para PNG/WEBP
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $gdImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($gdImage);
                $gdImage = $resized;
            } else {
                $newWidth  = $origWidth;
                $newHeight = $origHeight;
            }

            // 10. Guardar como WEBP en un buffer
            ob_start();
            imagewebp($gdImage, null, 85);
            $webpData = ob_get_clean();
            imagedestroy($gdImage);

            if ($webpData === false || strlen($webpData) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => '[DEBUG paso 10] imagewebp() no generó datos. GD puede no tener soporte WEBP.',
                ], 422);
            }

            // 11. Guardar en storage/app/public/posiciones/{posicion_id}.webp
            $relativePath = 'posiciones/' . $posicion_id . '.webp';
            Storage::disk('public')->put($relativePath, $webpData);

            return response()->json([
                'success' => true,
                'message' => 'Imagen registrada correctamente.',
                'data'    => [
                    'posicion_id'  => $posicion_id,
                    'imagen'       => $relativePath,
                    'url'          => Storage::disk('public')->url($relativePath),
                    'original'     => ['width' => $origWidth, 'height' => $origHeight],
                    'almacenada'   => ['width' => $newWidth,  'height' => $newHeight],
                ],
            ], 200);

        } catch (\Throwable $e) {
            if (isset($gdImage) && is_resource($gdImage)) {
                imagedestroy($gdImage);
            }
            return response()->json([
                'success' => false,
                'message' => '[DEBUG catch] ' . get_class($e) . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/recorridos/posiciones/{posicion_id}/imagen",
     *   summary="Obtener imagen de una posición",
     *   description="Devuelve la imagen en formato WEBP asociada a la posición indicada.",
     *   tags={"Recorridos"},
     *   @OA\Parameter(
     *     name="posicion_id",
     *     in="path",
     *     required=true,
     *     description="UUID de la posición.",
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(response=200, description="Imagen de la posición."),
     *   @OA\Response(response=404, description="Posición o imagen no encontrada.")
     * )
     */
    public function getImagenPosicion(string $posicion_id)
    {
        $posicion = Posicion::find($posicion_id);
        if (!$posicion) {
            return response()->json([
                'success' => false,
                'message' => 'La posición indicada no existe.',
            ], 404);
        }

        $relativePath = 'posiciones/' . $posicion_id . '.webp';

        if (!Storage::disk('public')->exists($relativePath)) {
            return response()->json([
                'success' => false,
                'message' => 'La posición no tiene una imagen registrada.',
            ], 404);
        }

        $imageData = Storage::disk('public')->get($relativePath);

        return response($imageData, 200)
            ->header('Content-Type', 'image/webp')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
