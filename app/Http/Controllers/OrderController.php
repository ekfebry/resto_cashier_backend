<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with('user', 'orderItems.product', 'orderItems.variant', 'payments');

        if ($request->user()->role === 'customer') {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'required|in:dine-in,takeaway,delivery',
            'table_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $total = 0;

            foreach ($request->items as $item) {
                $product = \App\Models\Product::find($item['product_id']);
                $price = $product->price;

                if (isset($item['variant_id'])) {
                    $variant = \App\Models\Variant::find($item['variant_id']);
                    $price += $variant->price_modifier;
                }

                $total += $price * $item['quantity'];
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'total' => $total,
                'order_type' => $request->order_type,
                'table_number' => $request->table_number,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $product = \App\Models\Product::find($item['product_id']);
                $price = $product->price;

                if (isset($item['variant_id'])) {
                    $variant = \App\Models\Variant::find($item['variant_id']);
                    $price += $variant->price_modifier;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $order->load('orderItems.product', 'orderItems.variant');
        });

        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        // Check if user can view this order
        if ($request->user()->role === 'customer' && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($order->load('user', 'orderItems.product', 'orderItems.variant', 'payments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // Only staff/admin can update status
        if (!in_array($request->user()->role, ['admin', 'staff', 'cashier'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'sometimes|in:pending,cooking,ready,delivered',
        ]);

        $order->update($request->only('status'));
        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        // Only admin can delete
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->delete();
        return response()->json(['message' => 'Order deleted']);
    }
}
