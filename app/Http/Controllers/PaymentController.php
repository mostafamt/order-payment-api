<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get all payments with optional filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Payment::with('order');

        // Filter by authenticated user's orders only
        $query->whereHas('order', function ($q) {
            $q->where('user_id', auth()->id());
        });

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter by payment method if provided
        if ($request->has('method')) {
            $query->where('method', $request->query('method'));
        }

        // Filter by order_id if provided
        if ($request->has('order_id')) {
            $query->where('order_id', $request->query('order_id'));
        }

        $payments = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Get a specific payment
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $payment = Payment::with('order')->find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Ensure user can only view their own payments
        if ($payment->order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this payment',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Process a payment for an order
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(ProcessPaymentRequest $request)
    {
        try {
            $data = $request->validated();

            // Get the order
            $order = Order::find($data['order_id']);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Ensure user owns the order
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to process payment for this order',
                ], 403);
            }

            // Process the payment through PaymentService
            $payment = $this->paymentService->processPayment(
                $order,
                $data['method'],
                $data['payment_data']
            );

            // Load the order relationship
            $payment->load('order');

            $statusCode = $payment->status === 'successful' ? 200 : 422;

            return response()->json([
                'success' => $payment->status === 'successful',
                'message' => $payment->meta['message'] ?? 'Payment processed',
                'data' => $payment,
            ], $statusCode);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
