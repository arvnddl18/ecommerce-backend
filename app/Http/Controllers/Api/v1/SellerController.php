<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SellerController extends Controller
{
    /**
     * Get or create seller profile for authenticated user.
     */
    protected function getSeller(Request $request): SellerProfile
    {
        $user = $request->user();
        abort_unless($user && ($user->isSeller() || $user->isAdmin()), 403, 'Unauthorized seller access.');

        /** @var SellerProfile $seller */
        $seller = $user->sellerProfile ?: $user->sellerProfile()->create([
            'store_name' => $user->name.' Apparel',
            'slug' => Str::slug($user->name.'-apparel-'.$user->id),
            'verification_status' => 'approved',
        ]);

        return $seller;
    }

    /**
     * Seller dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $orderItems = OrderItem::where('seller_id', $seller->id)
            ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->get();

        $totalRevenue = $orderItems->sum('total_price');
        $pendingFulfillment = $orderItems->where('fulfillment_status', 'pending')->count();
        $totalItemsSold = $orderItems->sum('quantity');

        $lowStockVariants = $seller->products()
            ->with(['variants' => fn ($q) => $q->where('stock_quantity', '<=', 5)])
            ->get()
            ->flatMap(function (Product $p) {
                return $p->variants;
            })
            ->values();

        return response()->json([
            'seller' => [
                'id' => $seller->id,
                'store_name' => $seller->store_name,
                'slug' => $seller->slug,
                'verification_status' => $seller->verification_status,
                'stripe_account_id' => $seller->stripe_account_id,
                'bio' => $seller->bio,
            ],
            'stats' => [
                'total_revenue' => $totalRevenue,
                'formatted_revenue' => '$'.number_format($totalRevenue / 100, 2),
                'pending_fulfillment' => $pendingFulfillment,
                'items_sold' => $totalItemsSold,
                'total_listings' => $seller->products()->count(),
                'low_stock_count' => $lowStockVariants->count(),
            ],
            'low_stock_alerts' => $lowStockVariants,
        ]);
    }

    /**
     * List products owned by this seller.
     */
    public function products(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $products = $seller->products()
            ->with(['category', 'variants', 'galleryImages'])
            ->latest('id')
            ->paginate(15);

        return response()->json($products);
    }

    /**
     * Create a new product listing with variants and images.
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sku' => ['required', 'string', 'unique:products,sku'],
            'variants' => ['sometimes', 'array'],
            'variants.*.size' => ['required_with:variants', 'string'],
            'variants.*.color' => ['required_with:variants', 'string'],
            'variants.*.sku' => ['required_with:variants', 'string', 'distinct'],
            'variants.*.stock_quantity' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.price_override' => ['nullable', 'integer', 'min:0'],
            'images' => ['sometimes', 'array'],
            'images.*' => ['string', 'url'],
        ]);

        /** @var Product $product */
        $product = $seller->products()->create([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(6),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'sku' => $validated['sku'],
            'images' => $validated['images'] ?? [],
            'is_active' => true,
            'status' => 'active',
        ]);

        if (! empty($validated['variants'])) {
            foreach ($validated['variants'] as $varData) {
                $product->variants()->create($varData);
            }
        }

        if (! empty($validated['images'])) {
            foreach ($validated['images'] as $idx => $url) {
                $product->galleryImages()->create([
                    'url' => $url,
                    'sort_order' => $idx,
                ]);
            }
        }

        $product->load(['variants', 'galleryImages', 'category']);

        return response()->json([
            'message' => 'Product listing created successfully.',
            'product' => $product,
        ], 201);
    }

    /**
     * Orders containing products from this seller.
     */
    public function orders(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $orderItems = OrderItem::where('seller_id', $seller->id)
            ->with(['order.user', 'variant', 'product'])
            ->latest('id')
            ->paginate(20);

        return response()->json($orderItems);
    }

    /**
     * Update fulfillment status for an order item.
     */
    public function updateFulfillment(Request $request, OrderItem $orderItem): JsonResponse
    {
        $seller = $this->getSeller($request);
        abort_unless($orderItem->seller_id === $seller->id || $request->user()?->isAdmin(), 403, 'Unauthorized.');

        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled'],
        ]);

        $orderItem->update([
            'fulfillment_status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Fulfillment status updated.',
            'order_item' => $orderItem->fresh(),
        ]);
    }

    /**
     * Setup or update Stripe Connect payout account.
     */
    public function payoutSetup(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $validated = $request->validate([
            'stripe_account_id' => ['sometimes', 'string', 'max:255'],
        ]);

        $accountId = $validated['stripe_account_id'] ?? ('acct_connect_'.Str::random(16));

        $seller->update([
            'stripe_account_id' => $accountId,
        ]);

        return response()->json([
            'message' => 'Stripe Connect payout account configured.',
            'stripe_account_id' => $seller->stripe_account_id,
            'payout_ready' => true,
        ]);
    }
}
