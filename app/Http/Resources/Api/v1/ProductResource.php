<?php

namespace App\Http\Resources\Api\v1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'seller' => $this->seller ? [
                'id' => $this->seller->id,
                'store_name' => $this->seller->store_name,
                'slug' => $this->seller->slug,
                'verification_status' => $this->seller->verification_status,
            ] : null,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price, // In centavos
            'formatted_price' => '₱'.$this->formatted_price,
            'stock' => $this->stock,
            'in_stock' => $this->inStock(),
            'sku' => $this->sku,
            'images' => $this->images ?? [],
            'variants' => $this->relationLoaded('variants') ? $this->variants->map(fn ($v) => [
                'id' => $v->id,
                'size' => $v->size,
                'color' => $v->color,
                'sku' => $v->sku,
                'stock_quantity' => $v->stock_quantity,
                'price_override' => $v->price_override,
                'effective_price' => $v->getEffectivePrice(),
                'formatted_effective_price' => '₱'.number_format($v->getEffectivePrice() / 100, 2),
            ]) : [],
            'gallery_images' => $this->relationLoaded('galleryImages') ? $this->galleryImages->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'sort_order' => $img->sort_order,
            ]) : [],
            'average_rating' => $this->relationLoaded('reviews') && $this->reviews->isNotEmpty()
                ? round($this->reviews->avg('rating'), 1)
                : 4.8,
            'reviews_count' => $this->relationLoaded('reviews') ? $this->reviews->count() : 0,
            'reviews' => $this->relationLoaded('reviews') ? $this->reviews->take(10)->map(fn ($r) => [
                'id' => $r->id,
                'user_name' => $r->user->name ?? 'Verified Shopper',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at?->diffForHumans(),
            ]) : [],
            'is_active' => $this->is_active,
            'status' => $this->status ?? 'active',
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
