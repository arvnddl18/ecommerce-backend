<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $seller_id
 * @property int|null $variant_id
 * @property array<string, mixed>|null $variant_details
 * @property string $product_name
 * @property int $unit_price
 * @property int $quantity
 * @property int $total_price
 * @property string $fulfillment_status
 * @property string|null $order_number
 * @property string|null $order_status
 * @property string|null $formatted_total
 * @property string|null $sku
 * @property string|null $size
 * @property string|null $color
 * @property-read Order|null $order
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 * @property-read SellerProfile|null $seller
 */
#[Fillable([
    'order_id',
    'product_id',
    'seller_id',
    'variant_id',
    'variant_details',
    'product_name',
    'unit_price',
    'quantity',
    'total_price',
    'fulfillment_status',
])]
class OrderItem extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'total_price' => 'integer',
            'variant_details' => 'array',
        ];
    }

    /**
     * Order that contains this item.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Product referenced by this line item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Seller fulfilling this item.
     *
     * @return BelongsTo<SellerProfile, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id');
    }

    /**
     * Apparel variant for this item.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Customer review tied to this verified purchase line item.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
