<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Variant::with('product');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'price_modifier' => 'numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ]);

        $variant = Variant::create($request->all());
        return response()->json($variant->load('product'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Variant $variant)
    {
        return response()->json($variant->load('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Variant $variant)
    {
        $request->validate([
            'product_id' => 'sometimes|required|exists:products,id',
            'name' => 'sometimes|required|string|max:255',
            'price_modifier' => 'sometimes|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ]);

        $variant->update($request->all());
        return response()->json($variant->load('product'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Variant $variant)
    {
        $variant->delete();
        return response()->json(['message' => 'Variant deleted']);
    }
}
