<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int|null $seller_id
 * @property int|null $discount_percent
 * @property int|null $discount_amount
 * @property int $min_order_amount
 * @property int|null $max_uses
 * @property int $uses_count
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SellerProfile|null $seller
 */
#[Fillable([
    'code',
    'seller_id',
    'discount_percent',
    'discount_amount',
    'min_order_amount',
    'max_uses',
    'uses_count',
    'expires_at',
    'is_active',
])]
class Coupon extends Model
{
    use HasFactory;

    /**
     * Get attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'discount_amount' => 'integer',
            'min_order_amount' => 'integer',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Seller profile this coupon is scoped to (or null for platform-wide).
     *
     * @return BelongsTo<SellerProfile, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    /**
     * Check if coupon is currently valid for use.
     */
    public function isValid(?int $subtotal = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        if ($subtotal !== null && $this->min_order_amount > 0 && $subtotal < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount in cents for given subtotal in cents.
     */
    public function calculateDiscount(int $subtotal): int
    {
        if (! $this->isValid() || $subtotal < $this->min_order_amount) {
            return 0;
        }

        if ($this->discount_percent !== null && $this->discount_percent > 0) {
            $discount = (int) round(($subtotal * $this->discount_percent) / 100);

            return min($discount, $subtotal);
        }

        if ($this->discount_amount !== null && $this->discount_amount > 0) {
            return min($this->discount_amount, $subtotal);
        }

        return 0;
    }
}
