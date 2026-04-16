<?php

namespace App\Http\Controllers;

use App\Models\Container; // Pastikan Model Container sudah dibuat
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class ContainerController extends Controller
{
    /**
     * GET /api/containers - Menampilkan semua container dari database
     */
    public function index()
    {
        $containers = Container::all();
        return response()->json([
            'success' => true,
            'data' => $containers
        ], 200);
    }

    /**
     * POST /api/containers - Membuat container baru di database
     */
    public function store(Request $request)
    {
        // Validasi Input
        $validator = Validator::make($request->all(), [
            'container_id' => [
                'required',
                'regex:/^[A-Z]{2}\d{5}$/', // Format: 2 Huruf + 5 Angka
                'unique:containers,container_id' // Cek unique langsung ke tabel MySQL
            ],
            'waste_type' => 'required|string',
            'weight_kg' => [
                'required',
                'numeric',
                'min:10',
                'max:5000',
                function ($attribute, $value, $fail) use ($request) {
                    // Conditional Validation: Jika waste_type = Chemical, max 1000
                    if ($request->waste_type === 'Chemical' && $value > 1000) {
                        $fail('Berat limbah Chemical tidak boleh lebih dari 1000 kg.');
                    }
                }
            ],
            'status' => 'required|in:Active,Archived'
        ], [
            'container_id.required' => 'Container ID wajib diisi.',
            'container_id.regex' => 'Format Container ID harus 2 huruf kapital diikuti 5 angka (contoh: GD12345).',
            'container_id.unique' => 'Container ID sudah digunakan.',
            'waste_type.required' => 'Tipe limbah wajib diisi.',
            'weight_kg.required' => 'Berat wajib diisi.',
            'weight_kg.numeric' => 'Berat harus berupa angka.',
            'weight_kg.min' => 'Berat minimal 10 kg.',
            'weight_kg.max' => 'Berat maksimal 5000 kg.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status hanya boleh Active atau Archived.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simpan ke database MySQL
        $container = Container::create([
            'container_id' => $request->container_id,
            'waste_type' => $request->waste_type,
            'weight_kg' => (float) $request->weight_kg,
            'status' => $request->status,
            'tracking_logs' => [
                [
                    'location' => 'Initial Entry',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'description' => 'Container created via API (MySQL Storage)'
                ]
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Container berhasil ditambahkan',
            'data' => $container
        ], 201);
    }

    /**
     * GET /api/containers/{id} - Menampilkan detail container
     */
    public function show($id)
    {
        $container = Container::where('container_id', $id)->first();

        if ($container) {
            return response()->json([
                'success' => true,
                'data' => $container
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Container tidak ditemukan'
        ], 404);
    }

    /**
     * PATCH /api/containers/{id} - Update container (Archive)
     */
    public function update(Request $request, $id)
    {
        $container = Container::where('container_id', $id)->first();

        if ($container) {
            $newStatus = $request->status ?? 'Archived';
            
            // Update Logs
            $logs = $container->tracking_logs;
            $logs[] = [
                'location' => 'System Update',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'description' => 'Status changed to ' . $newStatus
            ];

            $container->update([
                'status' => $newStatus,
                'tracking_logs' => $logs
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Container berhasil diupdate',
                'data' => $container
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Container tidak ditemukan'
        ], 404);
    }

    /**
     * DELETE /api/containers/{id} - Hapus container secara permanen
     */
    public function destroy($id)
    {
        $container = Container::where('container_id', $id)->first();

        if ($container) {
            $container->delete();

            return response()->json([
                'success' => true,
                'message' => 'Container berhasil dihapus',
                'data' => $container
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Container tidak ditemukan'
        ], 404);
    }

    /**
     * GET /api/containers/search/filter - Search & Filter menggunakan Query Database
     */
    public function search(Request $request)
    {
        $query = Container::query();

        if ($request->has('type')) {
            $query->where('waste_type', 'like', '%' . $request->type . '%');
        }

        if ($request->has('min_weight')) {
            $query->where('weight_kg', '>=', (float) $request->min_weight);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ], 200);
    }

    /**
     * GET /api/containers/{id}/logs - Nested Resource: Tracking Logs
     */
    public function getLogs($id)
    {
        $container = Container::where('container_id', $id)->first();

        if ($container) {
            return response()->json([
                'success' => true,
                'container_id' => $id,
                'logs' => $container->tracking_logs
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Container tidak ditemukan'
        ], 404);
    }
}