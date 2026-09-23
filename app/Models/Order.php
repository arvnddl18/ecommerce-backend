<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $order_number
 * @property string $status
 * @property int $total_amount
 * @property string $currency
 * @property string|null $stripe_session_id
 * @property string|null $stripe_payment_intent_id
 * @property string $customer_email
 * @property string|null $customer_name
 * @property array<string, mixed>|null $shipping_address
 * @property array<string, mixed>|null $billing_address
 * @property array<string, mixed>|null $metadata
 * @property-read Collection<int, OrderItem> $items
 * @property-read User|null $user
 */
#[Fillable([
    'user_id',
    'order_number',
    'status',
    'total_amount',
    'currency',
    'stripe_session_id',
    'stripe_payment_intent_id',
    'customer_email',
    'customer_name',
    'shipping_address',
    'billing_address',
    'metadata',
])]
class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * User who placed the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Purchased line items.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Formatted total amount in standard currency string.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount / 100, 2);
    }
}
