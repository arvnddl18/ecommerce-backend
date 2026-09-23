<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Refund;
use Stripe\Stripe;

class AdminController extends Controller
{
    /**
     * Ensure user has admin privileges.
     */
    protected function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403, 'Unauthorized admin access.');
    }

    /**
     * List all seller accounts for moderation.
     */
    public function sellers(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $status = $request->query('status');

        $query = SellerProfile::with('user')->latest('id');

        if ($status) {
            $query->where('verification_status', $status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Approve or reject a seller application.
     */
    public function updateSellerStatus(Request $request, SellerProfile $seller): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
        ]);

        $seller->update([
            'verification_status' => $validated['status'],
        ]);

        return response()->json([
            'message' => "Seller application status updated to {$validated['status']}.",
            'seller' => $seller->fresh('user'),
        ]);
    }

    /**
     * Platform-wide marketplace analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $totalGmv = Order::where('status', '!=', 'cancelled')->sum('total_amount');
        $totalOrders = Order::count();
        $activeSellers = SellerProfile::where('verification_status', 'approved')->count();
        $pendingSellers = SellerProfile::where('verification_status', 'pending')->count();
        $totalBuyers = User::where('role', 'buyer')->count();

        $recentOrders = Order::with(['user', 'items'])
            ->latest('id')
            ->take(10)
            ->get();

        return response()->json([
            'overview' => [
                'total_gmv' => $totalGmv,
                'formatted_gmv' => '₱'.number_format($totalGmv / 100, 2),
                'total_orders' => $totalOrders,
                'active_sellers' => $activeSellers,
                'pending_sellers' => $pendingSellers,
                'total_buyers' => $totalBuyers,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * Create a new category (supports hierarchical parent_id).
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:categories,slug,'.$category->id],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => $category->fresh(['parent', 'children']),
        ]);
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(Request $request, Category $category): JsonResponse
    {
        $this->authorizeAdmin($request);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Ban or unban a platform user.
     */
    public function toggleUserBan(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'is_banned' => ['required', 'boolean'],
        ]);

        $user->update([
            'is_banned' => $validated['is_banned'],
        ]);

        return response()->json([
            'message' => $validated['is_banned'] ? 'User has been suspended.' : 'User suspension lifted.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * List all platform orders for administrative review.
     */
    public function orders(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = Order::with(['user', 'items'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Issue an administrative refund and atomically restock product and variant inventory.
     */
    public function refundOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($order->status === Order::STATUS_REFUNDED) {
            return response()->json(['message' => 'Order has already been refunded.'], 422);
        }

        $stripeSecret = config('services.stripe.secret');
        $refundId = null;

        if ($order->stripe_payment_intent_id && $stripeSecret) {
            try {
                Stripe::setApiKey($stripeSecret);
                $refund = Refund::create([
                    'payment_intent' => $order->stripe_payment_intent_id,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => (string) $order->order_number,
                    ],
                ]);
                $refundId = $refund->id;
            } catch (\Throwable $e) {
                Log::warning("Stripe refund warning for order #{$order->order_number}: {$e->getMessage()}");
            }
        }

        DB::transaction(function () use ($order, $refundId): void {
            $metadata = $order->metadata ?? [];
            if ($refundId) {
                $metadata['stripe_refund_id'] = $refundId;
            }

            $order->update([
                'status' => Order::STATUS_REFUNDED,
                'metadata' => $metadata,
            ]);

            // Restore inventory for both variants and base products
            foreach ($order->items as $item) {
                if ($item->variant_id) {
                    ProductVariant::where('id', $item->variant_id)
                        ->increment('stock_quantity', $item->quantity);
                }

                if ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->increment('stock', $item->quantity);
                }
            }
        });

        return response()->json([
            'message' => 'Order refunded and inventory restocked successfully.',
            'order' => $order->fresh(['items']),
        ]);
    }
}
