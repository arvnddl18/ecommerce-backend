<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Cache TTL in seconds (7 days for carts).
     */
    protected int $ttl = 604800;

    /**
     * Get formatted cart data with calculated totals and enriched product information.
     *
     * @return array{items: array<int, array<string, mixed>>, total_quantity: int, subtotal: int, formatted_subtotal: string}
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
            ];
        }

        $productIds = array_keys($rawItems);
        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0;
        $totalQuantity = 0;

        foreach ($rawItems as $productId => $qty) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $quantity = min($qty, max(0, $product->stock));

            if ($quantity <= 0) {
                continue;
            }

            $itemTotal = $product->price * $quantity;
            $subtotal += $itemTotal;
            $totalQuantity += $quantity;

            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
                'formatted_price' => '$'.$product->formatted_price,
                'quantity' => $quantity,
                'stock' => $product->stock,
                'total' => $itemTotal,
                'formatted_total' => '$'.number_format($itemTotal / 100, 2),
                'image' => $product->images[0] ?? null,
            ];
        }

        return [
            'items' => $items,
            'total_quantity' => $totalQuantity,
            'subtotal' => $subtotal,
            'formatted_subtotal' => '$'.number_format($subtotal / 100, 2),
        ];
    }

    /**
     * Add item to cart with stock validation.
     *
     * @return array<string, mixed>
     */
    public function addItem(string $identifier, int $productId, int $quantity = 1): array
    {
        $product = Product::findOrFail($productId);

        if (! $product->is_active) {
            throw ValidationException::withMessages(['product' => 'This product is no longer active.']);
        }

        $rawItems = Cache::get($this->cacheKey($identifier), []);
        $currentQty = $rawItems[$productId] ?? 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Cannot add {$quantity} more. Only {$product->stock} in stock.",
            ]);
        }

        $rawItems[$productId] = $newQty;
        Cache::put($this->cacheKey($identifier), $rawItems, $this->ttl);

        return $this->getCart($identifier);
    }

    /**
     * Update item quantity in cart.
     *
     * @return array<string, mixed>
     */
    public function updateQuantity(string $identifier, int $productId, int $quantity): array
    {
        $product = Product::findOrFail($productId);

        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Requested quantity exceeds available stock ({$product->stock}).",
            ]);
        }

        $rawItems = Cache::get($this->cacheKey($identifier), []);

        if ($quantity <= 0) {
            unset($rawItems[$productId]);
        } else {
            $rawItems[$productId] = $quantity;
        }

        Cache::put($this->cacheKey($identifier), $rawItems, $this->ttl);

        return $this->getCart($identifier);
    }

    /**
     * Remove item from cart.
     *
     * @return array<string, mixed>
     */
    public function removeItem(string $identifier, int $productId): array
    {
        $rawItems = Cache::get($this->cacheKey($identifier), []);
        unset($rawItems[$productId]);
        Cache::put($this->cacheKey($identifier), $rawItems, $this->ttl);

        return $this->getCart($identifier);
    }

    /**
     * Clear all items in cart.
     */
    public function clearCart(string $identifier): void
    {
        Cache::forget($this->cacheKey($identifier));
    }

    /**
     * Construct cache key for cart storage.
     */
    protected function cacheKey(string $identifier): string
    {
        return 'cart:'.$identifier;
    }
}
