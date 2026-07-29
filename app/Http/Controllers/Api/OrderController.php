<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function myOrders(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        try {
            $order = DB::transaction(function () use ($request) {
                $totalPrice = 0;
                $orderItemsData = [];

                foreach ($request->items as $item) {
                    $product = Product::where('id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($product->stock < $item['quantity']) {
                        throw new Exception("المخزون غير كافٍ للمنتج: {$product->title}. المتاح حالياً: {$product->stock}");
                    }

                    $product->decrement('stock', $item['quantity']);

                    $itemPrice = $product->price * $item['quantity'];
                    $totalPrice += $itemPrice;

                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'quantity'   => $item['quantity'],
                        'unit_price' => $product->price,
                    ];
                }

                $order = Order::create([
                    'user_id'     => $request->user()->id,
                    'total_price' => $totalPrice,
                    'status'      => 'pending',
                ]);

                foreach ($orderItemsData as $itemData) {
                    $order->items()->create($itemData);
                }

                OrderStatusHistory::create([
                    'order_id'            => $order->id,
                    'previous_status'     => null,
                    'new_status'          => 'pending',
                    'user_id'  => $request->user()->id,
                ]);

                return $order;
            });

            return response()->json([
                'message' => 'Order created successfully',
                'data'    => $order->load('items.product'),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function index()
    {
        $orders = Order::with(['user', 'items.product', 'statusHistories'])->latest()->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $previousStatus = $order->status;
        $newStatus = $request->status;

        if ($previousStatus === $newStatus) {
            return response()->json([
                'message' => 'الطلب بالفعل في هذه الحالة.',
            ], 400);
        }

        DB::transaction(function () use ($order, $previousStatus, $newStatus, $request) {
            $order->update(['status' => $newStatus]);

            OrderStatusHistory::create([
                'order_id'           => $order->id,
                'previous_status'    => $previousStatus,
                'new_status'         => $newStatus,
                'user_id' => $request->user()->id,
            ]);
        });

        return response()->json([
            'message' => 'Order status updated successfully',
            'data'    => $order->load('statusHistories'),
        ]);
    }
}
