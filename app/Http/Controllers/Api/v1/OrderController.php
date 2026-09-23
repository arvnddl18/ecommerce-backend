<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get paginated list of orders for authenticated buyer.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401, 'Unauthenticated.');

        $orders = Order::where('user_id', $user->id)
            ->with([
                'items.product.galleryImages',
                'items.variant',
                'items.seller',
            ])
            ->latest('id')
            ->paginate(15);

        return response()->json($orders);
    }

    /**
     * Get a specific order detail for authenticated buyer.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401, 'Unauthenticated.');
        abort_unless($order->user_id === $user->id || $user->isAdmin(), 403, 'Unauthorized order access.');

        $order->load([
            'items.product.galleryImages',
            'items.variant',
            'items.seller',
        ]);

        return response()->json([
            'order' => $order,
        ]);
    }
}
