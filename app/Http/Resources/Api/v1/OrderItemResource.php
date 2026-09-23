<?php

namespace App\Http\Resources\Api\v1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'seller_id' => $this->seller_id,
            'variant_id' => $this->variant_id,
            'variant_details' => $this->variant_details,
            'fulfillment_status' => $this->fulfillment_status,
            'product_name' => $this->product_name,
            'unit_price' => $this->unit_price,
            'formatted_unit_price' => '₱'.number_format($this->unit_price / 100, 2),
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'formatted_total_price' => '₱'.number_format($this->total_price / 100, 2),
        ];
    }
}
