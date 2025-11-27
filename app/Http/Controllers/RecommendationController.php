<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function recommendations(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'cart_items' => 'nullable|array',
            'cart_items.*' => 'integer|exists:products,id',
            'context' => 'nullable|array',
        ]);

        $userId = $request->user_id;
        $cartItems = $request->cart_items ?? [];
        $context = $request->context ?? [];

        $recommendations = [];

        // Rule-based: Best-sellers
        $bestSellers = OrderItem::select('product_id', \DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->pluck('product_id')
            ->toArray();

        foreach ($bestSellers as $productId) {
            if (!in_array($productId, $cartItems)) {
                $product = Product::find($productId);
                $recommendations[] = [
                    'product_id' => $productId,
                    'score' => 0.8,
                    'reason' => 'Best seller',
                ];
            }
        }

        // Frequently bought together
        if (!empty($cartItems)) {
            $relatedProducts = OrderItem::whereIn('product_id', $cartItems)
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('order_items as oi2', 'orders.id', '=', 'oi2.order_id')
                ->whereNotIn('oi2.product_id', $cartItems)
                ->select('oi2.product_id', \DB::raw('COUNT(*) as frequency'))
                ->groupBy('oi2.product_id')
                ->orderBy('frequency', 'desc')
                ->limit(3)
                ->pluck('product_id')
                ->toArray();

            foreach ($relatedProducts as $productId) {
                $recommendations[] = [
                    'product_id' => $productId,
                    'score' => 0.9,
                    'reason' => 'Frequently bought together',
                ];
            }
        }

        // User history based
        if ($userId) {
            $userHistory = OrderItem::whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->pluck('product_id')->unique()->toArray();

            $similarUsers = OrderItem::whereIn('product_id', $userHistory)
                ->whereHas('order', function ($q) use ($userId) {
                    $q->where('user_id', '!=', $userId);
                })
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('order_items as oi2', 'orders.id', '=', 'oi2.order_id')
                ->whereNotIn('oi2.product_id', array_merge($cartItems, $userHistory))
                ->select('oi2.product_id', \DB::raw('COUNT(*) as score'))
                ->groupBy('oi2.product_id')
                ->orderBy('score', 'desc')
                ->limit(3)
                ->get();

            foreach ($similarUsers as $item) {
                $recommendations[] = [
                    'product_id' => $item->product_id,
                    'score' => min(1.0, $item->score / 10), // normalize
                    'reason' => 'Based on your history',
                ];
            }
        }

        // Remove duplicates and sort by score
        $unique = [];
        foreach ($recommendations as $rec) {
            $key = $rec['product_id'];
            if (!isset($unique[$key]) || $unique[$key]['score'] < $rec['score']) {
                $unique[$key] = $rec;
            }
        }

        $recommendations = array_values($unique);
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return response()->json($recommendations);
    }
}
