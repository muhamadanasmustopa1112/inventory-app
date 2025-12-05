<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
 
    public function index(Request $request)
    {
        $query = Warehouse::query()->orderBy('name');

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }

        $perPage   = (int) $request->get('per_page', 10);
        $warehouses = $query->paginate($perPage);

        return response()->json($warehouses);
    }

 
    public function show(Warehouse $warehouse)
    {
        return response()->json($warehouse);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'city'      => ['nullable', 'string', 'max:100'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! array_key_exists('is_active', $data)) {
            $data['is_active'] = true;
        }

        $warehouse = Warehouse::create($data);

        return response()->json([
            'message'   => 'Warehouse berhasil dibuat',
            'data'      => $warehouse,
        ], 201);
    }


    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'code'      => ['sometimes', 'required', 'string', 'max:50', 'unique:warehouses,code,' . $warehouse->id],
            'city'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'address'   => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $warehouse->update($data);

        return response()->json([
            'message'   => 'Warehouse berhasil diupdate',
            'data'      => $warehouse,
        ]);
    }

 
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse berhasil dihapus',
        ]);
    }
}
