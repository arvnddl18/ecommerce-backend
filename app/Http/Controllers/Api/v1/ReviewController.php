<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a verified purchase customer review.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        // Check if user has purchased this product
        $orderItem = OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered']);
            })
            ->first();

        if (! $orderItem && ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Only verified purchasers of this item can submit a review.',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_item_id' => $orderItem?->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'review' => [
                'id' => $review->id,
                'user_name' => $user->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->diffForHumans(),
            ],
        ], 201);
    }
}
