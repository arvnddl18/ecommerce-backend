<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * List user's wishlisted products.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $productIds = Wishlist::where('user_id', $user->id)->pluck('product_id');
        $products = Product::whereIn('id', $productIds)
            ->with(['category', 'seller', 'variants', 'galleryImages'])
            ->get();

        return response()->json([
            'data' => ProductResource::collection($products),
            'count' => $products->count(),
        ]);
    }

    /**
     * Toggle a product in user's wishlist.
     */
    public function toggle(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $entry = Wishlist::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($entry) {
            $entry->delete();
            $isWishlisted = false;
            $message = 'Removed from wishlist.';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
            $isWishlisted = true;
            $message = 'Added to wishlist.';
        }

        return response()->json([
            'message' => $message,
            'is_wishlisted' => $isWishlisted,
            'product_id' => $product->id,
        ]);
    }
}
