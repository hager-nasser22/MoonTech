<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Notifications\ProductStockAvailable;
use Illuminate\Support\Facades\Notification;
class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    public function show(Product $product)
    {
        return response()->json([
            'data' => $product,
        ]);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'title'       => 'required|string|max:255',
        'description' => 'nullable|string',
        'price'       => 'required|numeric|min:0',
        'stock'       => 'required|integer|min:0',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', 
    ]);

    if ($request->hasFile('image')) {
        $validated['image'] = $request->file('image')->store('products', 'public');
    }

    $product = Product::create($validated);

    return response()->json([
        'message' => 'Product created successfully',
        'data'    => $product,
    ], 201);
}

 public function update(Request $request, Product $product)
{
    $validated = $request->validate([
        'title'       => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'price'       => 'sometimes|required|numeric|min:0',
        'stock'       => 'sometimes|required|integer|min:0',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);

    $oldStock = $product->stock;

    if ($request->hasFile('image')) {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $validated['image'] = $request->file('image')->store('products', 'public');
    }

    $product->update($validated);

    if ($oldStock == 0 && $product->stock > 0) {
        $subscribers = $product->subscribers; 

        if ($subscribers->isNotEmpty()) {
            Notification::send($subscribers, new ProductStockAvailable($product));
        }
    }

    return response()->json([
        'message' => 'Product updated successfully',
        'data'    => $product,
    ]);
}

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    public function subscribeToStock(Request $request, Product $product)
    {
        if ($product->stock > 0) {
            return response()->json([
                'message' => 'Product is already in stock',
            ], 400);
        }

        $user = $request->user();

        $product->subscribers()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => 'Successfully Subscribe',
        ]);
    }
}
