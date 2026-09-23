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

        // Auto-associate any unlinked guest orders placed with the user's verified email
        Order::whereNull('user_id')
            ->where('customer_email', $user->email)
            ->update(['user_id' => $user->id]);

        $orders = Order::where(function ($query) use ($user): void {
            $query->where('user_id', $user->id)
                ->orWhere('customer_email', $user->email);
        })
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

        $isOwner = $order->user_id === $user->id || $order->customer_email === $user->email;
        abort_unless($isOwner || $user->isAdmin(), 403, 'Unauthorized order access.');

        if ($order->user_id === null && $order->customer_email === $user->email) {
            $order->update(['user_id' => $user->id]);
        }

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
