<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use Illuminate\Http\Request;

class BuyerController extends Controller
{

    public function index(Request $request)
    {
        $query = Buyer::query()->orderBy('name');

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->get('per_page', 10);

        $buyers = $query->paginate($perPage);

        return response()->json($buyers);
    }


    public function show(Buyer $buyer)
    {
        return response()->json($buyer);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'type'    => ['nullable', 'string', 'max:50'],
        ]);

        $buyer = Buyer::create($data);

        return response()->json([
            'message' => 'Buyer berhasil dibuat',
            'data'    => $buyer,
        ], 201);
    }

    public function update(Request $request, Buyer $buyer)
    {
        $data = $request->validate([
            'name'    => ['sometimes', 'required', 'string', 'max:255'],
            'phone'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string'],
            'type'    => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $buyer->update($data);

        return response()->json([
            'message' => 'Buyer berhasil diupdate',
            'data'    => $buyer,
        ]);
    }

    public function destroy(Buyer $buyer)
    {
        $buyer->delete();

        return response()->json([
            'message' => 'Buyer berhasil dihapus',
        ]);
    }
}
