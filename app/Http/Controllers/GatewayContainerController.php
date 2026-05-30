<?php

namespace App\Http\Controllers;

use App\Models\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="WowoClean API",
 *     version="1.0.0",
 *     description="API untuk sistem manajemen limbah WowoClean"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Containers Gateway",
 *     description="API Gateway endpoints untuk manajemen containers dengan authorization"
 * )
 */
class GatewayContainerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/gateway/containers",
     *     summary="Dapatkan semua containers (admin dan user bisa akses)",
     *     tags={"Containers Gateway"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List semua containers dengan tracking logs"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - token tidak valid"
     *     )
     * )
     */
    public function index()
    {
        $containers = Container::with('trackingLogs')->get();

        return response()->json([
            'success' => true,
            'data' => $containers
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/gateway/containers",
     *     summary="Buat container baru (hanya admin)",
     *     tags={"Containers Gateway"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"container_id","waste_type","weight_kg","status"},
     *             @OA\Property(property="container_id", type="string", example="AB12345"),
     *             @OA\Property(property="waste_type", type="string", example="Chemical"),
     *             @OA\Property(property="weight_kg", type="number", example=850),
     *             @OA\Property(property="status", type="string", enum={"Active","Archived"}, example="Active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Container berhasil dibuat"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - hanya admin yang bisa create"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'container_id' => [
                'required',
                'unique:containers,container_id',
                'regex:/^[A-Z]{2}\d{5}$/'
            ],
            'waste_type' => 'required|string',
            'weight_kg' => [
                'required',
                'numeric',
                'min:10',
                'max:5000',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->waste_type === 'Chemical' && $value > 1000) {
                        $fail('Berat limbah Chemical tidak boleh lebih dari 1000 kg.');
                    }
                }
            ],
            'status' => 'required|in:Active,Archived'
        ], [
            'container_id.regex' => 'Format Container ID harus 2 huruf kapital diikuti 5 angka (contoh: AB12345).',
            'weight_kg.min' => 'Berat minimal 10 kg.',
            'weight_kg.max' => 'Berat maksimal 5000 kg.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $container = Container::create($request->all());

        // Tambah initial tracking log
        $container->trackingLogs()->create([
            'location' => 'Initial Entry',
            'timestamp' => now(),
            'description' => 'Container created via API'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Container berhasil dibuat',
            'data' => $container
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/gateway/containers/{id}/logs",
     *     summary="Dapatkan tracking logs untuk container tertentu",
     *     tags={"Containers Gateway"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List tracking logs"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container tidak ditemukan"
     *     )
     * )
     */
    public function getLogs($id)
    {
        $container = Container::find($id);

        if (!$container) {
            return response()->json([
                'success' => false,
                'message' => 'Container tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'container_id' => $container->container_id,
            'logs' => $container->trackingLogs
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/gateway/containers/{id}",
     *     summary="Update status container (hanya admin)",
     *     tags={"Containers Gateway"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"Active","Archived"}, example="Archived")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Container berhasil diupdate"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - hanya admin"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container tidak ditemukan"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $container = Container::find($id);

        if (!$container) {
            return response()->json([
                'success' => false,
                'message' => 'Container tidak ditemukan'
            ], 404);
        }

        $container->update($request->all());

        // Tambah log tracking untuk update
        $container->trackingLogs()->create([
            'location' => 'System Update',
            'timestamp' => now(),
            'description' => 'Status changed to ' . $container->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Container berhasil diupdate',
            'data' => $container
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/gateway/containers/{id}",
     *     summary="Hapus container (hanya admin)",
     *     tags={"Containers Gateway"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Container berhasil dihapus"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - hanya admin"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Container tidak ditemukan"
     *     )
     * )
     */
    public function destroy($id)
    {
        $container = Container::find($id);

        if (!$container) {
            return response()->json([
                'success' => false,
                'message' => 'Container tidak ditemukan'
            ], 404);
        }

        $container->delete();

        return response()->json([
            'success' => true,
            'message' => 'Container berhasil dihapus'
        ], 200);
    }
}