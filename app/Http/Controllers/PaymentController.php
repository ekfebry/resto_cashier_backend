<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function process(Request $request, Order $order)
    {
        $request->validate([
            'method' => 'required|string',
        ]);

        // Check if order belongs to user or user is staff
        if ($request->user()->role === 'customer' && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Simulate payment gateway integration
        $transactionId = 'TXN_' . time() . '_' . $order->id;

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'method' => $request->method,
            'status' => 'pending',
            'transaction_id' => $transactionId,
            'gateway_response' => ['status' => 'initiated'],
        ]);

        // In real app, redirect to payment gateway or return payment URL
        return response()->json([
            'payment' => $payment,
            'payment_url' => 'https://payment-gateway.com/pay/' . $transactionId, // simulated
        ]);
    }

    public function webhook(Request $request)
    {
        // Simulate webhook handling
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status'); // 'completed' or 'failed'

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if ($payment) {
            $payment->update([
                'status' => $status,
                'gateway_response' => $request->all(),
            ]);

            // If payment completed, update order status or something
            if ($status === 'completed') {
                // Maybe update order status
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
