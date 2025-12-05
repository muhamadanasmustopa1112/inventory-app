<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Product::orderBy('name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sku'                => ['required', 'string', 'unique:products,sku'],
            'name'               => ['required', 'string'],
            'default_sell_price' => ['nullable', 'numeric'],
            'category'           => ['nullable', 'string'],
            'description'        => ['nullable', 'string'],
        ]);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return $product;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku'                => ['sometimes', 'string', 'unique:products,sku,' . $product->id],
            'name'               => ['sometimes', 'string'],
            'default_sell_price' => ['sometimes', 'numeric'],
            'category'           => ['sometimes', 'string', 'nullable'],
            'description'        => ['sometimes', 'string', 'nullable'],
        ]);

        $product->update($data);

        return $product;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
