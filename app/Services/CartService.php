<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Cache TTL in seconds (7 days for carts).
     */
    protected int $ttl = 604800;

    /**
     * Get formatted cart data with calculated totals and enriched product & variant information.
     *
     * @return array{items: array<int, array<string, mixed>>, total_quantity: int, subtotal: int, formatted_subtotal: string, coupon: array<string, mixed>|null, discount: int, formatted_discount: string, total: int, formatted_total: string}
     */
    public function getCart(string $identifier): array
    {
        $rawItems = Cache::get($this->cacheKey($identifier), []);

        if (empty($rawItems)) {
            return [
                'items' => [],
                'total_quantity' => 0,
                'subtotal' => 0,
                'formatted_subtotal' => '$0.00',
                'coupon' => null,
                'discount' => 0,
                'formatted_discount' => '$0.00',
                'total' => 0,
                'formatted_total' => '$0.00',
            ];
        }

        // Normalize raw items to structured array: key => [product_id, variant_id, quantity]
        $normalized = $this->normalizeRawItems($rawItems);

        $productIds = array_unique(array_column($normalized, 'product_id'));
        $variantIds = array_filter(array_unique(array_column($normalized, 'variant_id')));

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $variants = ! empty($variantIds)
            ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $items = [];
        $subtotal = 0;
        $totalQuantity = 0;

        foreach ($normalized as $key => $entry) {
            /** @var Product|null $product */
            $product = $products->get($entry['product_id']);

            if (! $product) {
                continue;
            }

            $variant = $entry['variant_id'] ? $variants->get($entry['variant_id']) : null;

            $stock = $variant ? $variant->stock_quantity : $product->stock;
            $quantity = min($entry['quantity'], max(0, $stock));

            if ($quantity <= 0) {
                continue;
            }

            $unitPrice = $variant ? $variant->getEffectivePrice() : $product->price;
            $itemTotal = $unitPrice * $quantity;

            $subtotal += $itemTotal;
            $totalQuantity += $quantity;

            $items[] = [
                'item_key' => (string) $key,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $variant ? $variant->sku : $product->sku,
                'size' => $variant?->size,
                'color' => $variant?->color,
                'variant_details' => $variant ? [
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'sku' => $variant->sku,
                ] : null,
                'price' => $unitPrice,
                'formatted_price' => '₱'.number_format($unitPrice / 100, 2),
                'quantity' => $quantity,
                'stock' => $stock,
                'total' => $itemTotal,
                'formatted_total' => '₱'.number_format($itemTotal / 100, 2),
                'image' => $product->images[0] ?? null,
            ];
        }

        $appliedCoupon = null;
        $discount = 0;
        $couponCode = Cache::get($this->couponCacheKey($identifier));

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValid($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
                $appliedCoupon = [
                    'code' => $coupon->code,
                    'discount_percent' => $coupon->discount_percent,
                    'discount_amount' => $coupon->discount_amount,
                    'calculated_discount' => $discount,
                    'formatted_discount' => '₱'.number_format($discount / 100, 2),
                ];
            } else {
                Cache::forget($this->couponCacheKey($identifier));
            }
        }

        $total = max(0, $subtotal - $discount);

        return [
            'items' => $items,
            'total_quantity' => $totalQuantity,
            'subtotal' => $subtotal,
            'formatted_subtotal' => '₱'.number_format($subtotal / 100, 2),
            'coupon' => $appliedCoupon,
            'discount' => $discount,
            'formatted_discount' => '₱'.number_format($discount / 100, 2),
            'total' => $total,
            'formatted_total' => '₱'.number_format($total / 100, 2),
        ];
    }

    /**
     * Add item to cart with stock validation (supporting both products and variants).
     *
     * @return array<string, mixed>
     */
    public function addItem(string $identifier, int $productId, int $quantity = 1, ?int $variantId = null): array
    {
        $product = Product::findOrFail($productId);

        if (! $product->is_active) {
            throw ValidationException::withMessages(['product' => 'This product is no longer active.']);
        }

        $variant = null;
        if ($variantId !== null) {
            /** @var ProductVariant $variant */
            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $productId)
                ->firstOrFail();
        }

        $rawItems = Cache::get($this->cacheKey($identifier), []);
        $itemKey = $variantId ? "{$productId}_{$variantId}" : (string) $productId;

        $currentQty = 0;
        if (isset($rawItems[$itemKey])) {
            $currentQty = is_array($rawItems[$itemKey]) ? ($rawItems[$itemKey]['quantity'] ?? 0) : (int) $rawItems[$itemKey];
        }

        $newQty = $currentQty + $quantity;
        $availableStock = $variant ? $variant->stock_quantity : $product->stock;

        if ($newQty > $availableStock) {
            $itemLabel = $variant ? "size {$variant->size} / {$variant->color}" : 'this product';
            throw ValidationException::withMessages([
                'quantity' => "Cannot add {$quantity} more. Only {$availableStock} in stock for {$itemLabel}.",
            ]);
        }

        $rawItems[$itemKey] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $newQty,
        ];

        Cache::put($this->cacheKey($identifier), $rawItems, $this->ttl);

        return $this->getCart($identifier);
    }

    /**
     * Update item quantity in cart.
     *
     * @return array<string, mixed>
     */
    public function updateQuantity(string $identifier, string|int $itemKey, int $quantity): array
    {
        $rawItems = Cache::get($this->cacheKey($identifier), []);
        $resolvedKey = $this->resolveItemKey($rawItems, (string) $itemKey);

        if (! $resolvedKey || ! isset($rawItems[$resolvedKey])) {
            return $this->getCart($identifier);
        }

        $entry = is_array($rawItems[$resolvedKey])
            ? $rawItems[$resolvedKey]
            : ['product_id' => (int) $resolvedKey, 'variant_id' => null, 'quantity' => (int) $rawItems[$resolvedKey]];

        if ($quantity <= 0) {
            unset($rawItems[$resolvedKey]);
            Cache::put($this->cacheKey($identifier), $rawItems, $this->ttl);

            return $this->getCart($identifier);
        }

        $product = Product::findOrFail($entry['product_id']);
        $availableStock = $product->stock;

        if (! empty($entry['variant_id'])) {
            $variant = ProductVariant::find($entry['variant_id']);
            if ($variant) {
                $availableStock = $variant->stock_quantity;
            }
        }

        if ($quantity > $availableStock) {
            throw ValidationException::withMessages([
                'quantity' => "Requested quantity exceeds available stock ({$availableStock}).",
            ]);
        }

        if (is_array($rawItems[$resolvedKey])) {
            $rawItems[$resolvedKey]['quantity'] = $quantity;
        } else {
            $rawItems[$resolvedKey] = $quantity;
        }

        Cache::put($this->cacheKey($identifier), $rawItems, $this->ttl);

        return $this->getCart($identifier);
    }

    /**
     * Remove item from cart.
     *
     * @return array<string, mixed>
     */
    public function removeItem(string $identifier, string|int $itemKey): array
    {
        $rawItems = Cache::get($this->cacheKey($identifier), []);
        $resolvedKey = $this->resolveItemKey($rawItems, (string) $itemKey);

        if ($resolvedKey && isset($rawItems[$resolvedKey])) {
            unset($rawItems[$resolvedKey]);
            Cache::put($this->cacheKey($identifier), $rawItems, $this->ttl);
        }

        return $this->getCart($identifier);
    }

    /**
     * Clear all items in cart.
     */
    public function clearCart(string $identifier): void
    {
        Cache::forget($this->cacheKey($identifier));
        Cache::forget($this->couponCacheKey($identifier));
    }

    /**
     * Apply coupon code to cart.
     *
     * @return array<string, mixed>
     */
    public function applyCoupon(string $identifier, string $code): array
    {
        $cleanCode = strtoupper(trim($code));
        $coupon = Coupon::where('code', $cleanCode)->first();

        if (! $coupon) {
            throw ValidationException::withMessages(['coupon' => 'Invalid coupon code.']);
        }

        $cart = $this->getCart($identifier);

        if (empty($cart['items']) || $cart['subtotal'] <= 0) {
            throw ValidationException::withMessages(['coupon' => 'Your cart is empty.']);
        }

        if (! $coupon->isValid($cart['subtotal'])) {
            throw ValidationException::withMessages(['coupon' => 'Coupon requirements not met or coupon has expired.']);
        }

        Cache::put($this->couponCacheKey($identifier), $coupon->code, $this->ttl);

        return $this->getCart($identifier);
    }

    /**
     * Remove applied coupon from cart.
     *
     * @return array<string, mixed>
     */
    public function removeCoupon(string $identifier): array
    {
        Cache::forget($this->couponCacheKey($identifier));

        return $this->getCart($identifier);
    }

    /**
     * Construct cache key for coupon storage.
     */
    protected function couponCacheKey(string $identifier): string
    {
        return 'cart:coupon:'.$identifier;
    }

    /**
     * Resolve unique item key from raw cache map, supporting both exact keys and integer product IDs.
     *
     * @param  array<string|int, mixed>  $rawItems
     */
    protected function resolveItemKey(array $rawItems, string $targetKey): ?string
    {
        if (isset($rawItems[$targetKey])) {
            return $targetKey;
        }

        // If targetKey is numeric, check if any entry has matching product_id
        if (is_numeric($targetKey)) {
            $productId = (int) $targetKey;
            foreach ($rawItems as $k => $val) {
                if (is_array($val) && ($val['product_id'] ?? null) === $productId) {
                    return (string) $k;
                }
                if (! is_array($val) && (int) $k === $productId) {
                    return (string) $k;
                }
            }
        }

        return null;
    }

    /**
     * Normalize heterogeneous raw cart items to uniform format.
     *
     * @param  array<string|int, mixed>  $rawItems
     * @return array<string, array{product_id: int, variant_id: int|null, quantity: int}>
     */
    protected function normalizeRawItems(array $rawItems): array
    {
        $normalized = [];

        foreach ($rawItems as $key => $val) {
            $keyStr = (string) $key;

            if (is_array($val)) {
                $normalized[$keyStr] = [
                    'product_id' => (int) $val['product_id'],
                    'variant_id' => isset($val['variant_id']) && $val['variant_id'] ? (int) $val['variant_id'] : null,
                    'quantity' => (int) ($val['quantity'] ?? 1),
                ];
            } else {
                // Legacy simple map: key is product_id (or product_variant), value is quantity
                if (str_contains($keyStr, '_')) {
                    [$pId, $vId] = explode('_', $keyStr, 2);
                    $normalized[$keyStr] = [
                        'product_id' => (int) $pId,
                        'variant_id' => (int) $vId,
                        'quantity' => (int) $val,
                    ];
                } else {
                    $normalized[$keyStr] = [
                        'product_id' => (int) $keyStr,
                        'variant_id' => null,
                        'quantity' => (int) $val,
                    ];
                }
            }
        }

        return $normalized;
    }

    /**
     * Construct cache key for cart storage.
     */
    protected function cacheKey(string $identifier): string
    {
        return 'cart:'.$identifier;
    }
}
