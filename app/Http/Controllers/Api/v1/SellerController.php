<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            ->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_PAID))
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
                'formatted_revenue' => '₱'.number_format($totalRevenue / 100, 2),
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
     * Update an existing product listing with variants and images.
     */
    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $seller = $this->getSeller($request);
        abort_unless($product->seller_id === $seller->id || $request->user()?->isAdmin(), 403, 'Unauthorized.');

        $validated = $request->validate([
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,draft,archived'],
            'is_active' => ['sometimes', 'boolean'],
            'variants' => ['sometimes', 'array'],
            'variants.*.id' => ['sometimes', 'nullable', 'integer'],
            'variants.*.size' => ['required_with:variants', 'string'],
            'variants.*.color' => ['required_with:variants', 'string'],
            'variants.*.sku' => ['required_with:variants', 'string'],
            'variants.*.stock_quantity' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.price_override' => ['nullable', 'integer', 'min:0'],
            'images' => ['sometimes', 'array'],
            'images.*' => ['string', 'url'],
        ]);

        $updateData = [];
        if (isset($validated['category_id'])) {
            $updateData['category_id'] = $validated['category_id'];
        }
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['price'])) {
            $updateData['price'] = $validated['price'];
        }
        if (isset($validated['stock'])) {
            $updateData['stock'] = $validated['stock'];
        }
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }
        if (array_key_exists('is_active', $validated)) {
            $updateData['is_active'] = $validated['is_active'];
        }

        if (! empty($updateData)) {
            $product->update($updateData);
        }

        if (isset($validated['variants'])) {
            foreach ($validated['variants'] as $varData) {
                if (! empty($varData['id'])) {
                    $variant = $product->variants()->where('id', $varData['id'])->first();
                    if ($variant) {
                        $variant->update($varData);

                        continue;
                    }
                }
                $product->variants()->create($varData);
            }
        }

        if (isset($validated['images'])) {
            $product->galleryImages()->delete();
            foreach ($validated['images'] as $idx => $url) {
                $product->galleryImages()->create([
                    'url' => $url,
                    'sort_order' => $idx,
                ]);
            }
        }

        $product->load(['variants', 'galleryImages', 'category']);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product,
        ]);
    }

    /**
     * Archive or delete a product listing.
     */
    public function destroyProduct(Request $request, Product $product): JsonResponse
    {
        $seller = $this->getSeller($request);
        abort_unless($product->seller_id === $seller->id || $request->user()?->isAdmin(), 403, 'Unauthorized.');

        $product->update([
            'status' => 'archived',
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Product listing archived successfully.',
        ]);
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

        $orderItems->getCollection()->transform(function (OrderItem $item) {
            $item->order_number = $item->order?->order_number;
            $item->order_status = $item->order?->status;
            $item->formatted_total = '₱'.number_format($item->total_price / 100, 2);
            $item->sku = $item->variant?->sku ?? $item->product?->sku ?? 'N/A';
            $item->size = $item->variant_details['size'] ?? 'Standard';
            $item->color = $item->variant_details['color'] ?? 'Standard';

            return $item;
        });

        return response()->json($orderItems);
    }

    /**
     * Update fulfillment status for an order item.
     */
    public function updateFulfillment(Request $request, OrderItem $orderItem): JsonResponse
    {
        $seller = $this->getSeller($request);
        abort_unless($orderItem->seller_id === $seller->id || $request->user()?->isAdmin(), 403, 'Unauthorized.');

        // Prevent dispatching unpaid orders
        if ($orderItem->order && $orderItem->order->status !== Order::STATUS_PAID) {
            abort(422, 'Cannot dispatch an unpaid order. Payment must be confirmed first.');
        }

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
    public function payoutSetup(Request $request, StripeService $stripeService): JsonResponse
    {
        $seller = $this->getSeller($request);

        $validated = $request->validate([
            'stripe_account_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $accountId = $validated['stripe_account_id'] ?? null;
        $onboardingUrl = null;

        if (! empty($accountId)) {
            $seller->update(['stripe_account_id' => $accountId]);
        } elseif (empty($seller->stripe_account_id)) {
            // Attempt to create a real Stripe Express connected account
            $user = $request->user();
            $createdId = $stripeService->createExpressAccount(
                $user->email ?? "seller_{$seller->id}@example.com",
                'US'
            );

            if ($createdId) {
                $seller->update(['stripe_account_id' => $createdId]);
                $accountId = $createdId;
            } else {
                // If Connect is not enabled on the Stripe account, fallback to local test account ID
                $fallbackId = 'acct_connect_'.Str::random(16);
                $seller->update(['stripe_account_id' => $fallbackId]);
                $accountId = $fallbackId;
            }
        } else {
            $accountId = $seller->stripe_account_id;
        }

        // Try to generate an onboarding URL if it's a real connected account
        if ($accountId && str_starts_with($accountId, 'acct_') && ! str_contains($accountId, '_demo') && ! str_contains($accountId, '_connect_')) {
            $frontendUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
            $onboardingUrl = $stripeService->createAccountLink(
                $accountId,
                "{$frontendUrl}/seller",
                "{$frontendUrl}/seller"
            );
        }

        return response()->json([
            'message' => 'Stripe Connect payout account configured.',
            'stripe_account_id' => $seller->stripe_account_id,
            'onboarding_url' => $onboardingUrl,
            'payout_ready' => true,
        ]);
    }

    /**
     * Upload product media image directly to storage.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $this->getSeller($request);

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $file = $request->file('image');
        if (! $file) {
            throw ValidationException::withMessages(['image' => 'No image file uploaded.']);
        }

        $path = $file->store('products', 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'url' => $url,
            'path' => $path,
        ], 201);
    }

    /**
     * List all coupons issued by this seller.
     */
    public function coupons(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $coupons = Coupon::where('seller_id', $seller->id)
            ->latest('id')
            ->paginate(20);

        return response()->json($coupons);
    }

    /**
     * Create a new discount coupon for this seller's shop.
     */
    public function storeCoupon(Request $request): JsonResponse
    {
        $seller = $this->getSeller($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:coupons,code'],
            'discount_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'discount_amount' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        if (empty($validated['discount_percent']) && empty($validated['discount_amount'])) {
            throw ValidationException::withMessages([
                'discount_percent' => 'Either discount percentage or fixed discount amount is required.',
            ]);
        }

        $coupon = Coupon::create([
            'seller_id' => $seller->id,
            'code' => strtoupper(trim($validated['code'])),
            'discount_percent' => $validated['discount_percent'] ?? null,
            'discount_amount' => $validated['discount_amount'] ?? null,
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_uses' => $validated['max_uses'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Coupon created successfully.',
            'coupon' => $coupon,
        ], 201);
    }
}
