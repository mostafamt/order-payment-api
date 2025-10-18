<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')->where('user_id', auth()->id());
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        $orders = $query->paginate(15);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();
        $order = Order::create([
            'user_id' => auth()->id(),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'shipping_name' => $data['shipping_name'] ?? null,
            'shipping_phone' => $data['shipping_phone'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'shipping_city' => $data['shipping_city'] ?? null,
            'shipping_province' => $data['shipping_province'] ?? null,
            'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
            'shipping_country' => $data['shipping_country'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['price'];
            $order->items()->create([
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'line_total' => $lineTotal,
            ]);
        }

        $order->recalculateTotal();

        return response()->json(['success' => true, 'data' => $order], 201);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);
        $order->load('items', 'payments');

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $this->authorize('update', $order);
        $data = $request->validated();

        if (isset($data['status'])) {
            // Business Rule: Orders with payments cannot be changed from confirmed to pending/cancelled
            if ($order->status === 'confirmed' && $order->payments()->exists()) {
                if (in_array($data['status'], ['pending', 'cancelled'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot change status of confirmed order with payments to pending or cancelled',
                    ], 400);
                }
            }

            $order->status = $data['status'];
            $order->save();
        }

        if (isset($data['items'])) {
            // simple approach: delete & recreate
            $order->items()->delete();
            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['price'];
                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'line_total' => $lineTotal,
                ]);
            }
            $order->recalculateTotal();
        }

        // Update shipping fields if provided
        $shippingFields = [
            'shipping_name',
            'shipping_phone',
            'shipping_address',
            'shipping_city',
            'shipping_province',
            'shipping_postal_code',
            'shipping_country',
        ];

        foreach ($shippingFields as $field) {
            if (isset($data[$field])) {
                $order->$field = $data[$field];
            }
        }

        if (count(array_intersect_key($data, array_flip($shippingFields))) > 0) {
            $order->save();
        }

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);
        if ($order->payments()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete order with payments'], 400);
        }
        $order->delete();

        return response()->json(['success' => true], 204);
    }
}
