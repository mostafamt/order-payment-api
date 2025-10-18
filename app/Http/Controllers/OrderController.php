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
