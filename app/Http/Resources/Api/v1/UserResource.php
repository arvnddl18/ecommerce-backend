<?php

namespace App\Http\Resources\Api\v1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'age_verified' => (bool) $this->age_verified,
            'seller_profile' => $this->sellerProfile ? [
                'id' => $this->sellerProfile->id,
                'store_name' => $this->sellerProfile->store_name,
                'slug' => $this->sellerProfile->slug,
                'verification_status' => $this->sellerProfile->verification_status,
                'stripe_account_id' => $this->sellerProfile->stripe_account_id,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
