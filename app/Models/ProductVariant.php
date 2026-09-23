<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'sku',
        'stock_quantity',
        'price_override',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'stock_quantity' => 'integer',
        'price_override' => 'integer',
    ];

    /**
     * Get the product that owns the variant.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the order items for this variant.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    /**
     * Get the effective price of the variant in cents.
     */
    public function getEffectivePrice(): int
    {
        return $this->price_override ?? $this->product->price;
    }

    /**
     * Check if variant is in stock.
     */
    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock_quantity >= $quantity;
    }
}
