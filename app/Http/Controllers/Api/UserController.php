<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('warehouse')
            ->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return response()->json($users);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'ADMIN') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => [
                'required',
                'string',
                Rule::in(['ADMIN', 'WAREHOUSE']), 
            ],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        if ($validated['role'] === 'WAREHOUSE' && empty($validated['warehouse_id'])) {
            return response()->json([
                'message' => 'warehouse_id wajib diisi untuk user WAREHOUSE',
                'errors'  => [
                    'warehouse_id' => ['Pilih gudang untuk user role WAREHOUSE.'],
                ],
            ], 422);
        }

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'role'         => $validated['role'],
            'warehouse_id' => $validated['role'] === 'WAREHOUSE'
                ? $validated['warehouse_id']
                : null,
        ]);

        $user->load('warehouse');

        return response()->json([
            'message' => 'User berhasil dibuat',
            'data'    => $user,
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        if ($request->user()->role !== 'ADMIN') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'role'     => ['sometimes', 'string', Rule::in(['ADMIN', 'WAREHOUSE'])],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        if (($validated['role'] ?? $user->role) === 'WAREHOUSE') {
            if (empty($validated['warehouse_id']) && empty($user->warehouse_id)) {
                return response()->json([
                    'message' => 'warehouse_id wajib diisi untuk user WAREHOUSE',
                    'errors'  => [
                        'warehouse_id' => ['Pilih gudang untuk user role WAREHOUSE.'],
                    ],
                ], 422);
            }
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->load('warehouse');

        return response()->json([
            'message' => 'User berhasil diperbarui',
            'data' => $user,
        ]);
    }

}
