<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_cannot_access_seller_portal_endpoints(): void
    {
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'age_verified' => true,
        ]);

        $response = $this->actingAs($buyer, 'sanctum')
            ->getJson(route('api.v1.seller.dashboard'));

        $response->assertStatus(403);
    }

    public function test_buyer_cannot_access_admin_portal_endpoints(): void
    {
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'age_verified' => true,
        ]);

        $response = $this->actingAs($buyer, 'sanctum')
            ->getJson(route('api.v1.admin.analytics'));

        $response->assertStatus(403);
    }

    public function test_seller_cannot_access_admin_portal_endpoints(): void
    {
        $sellerUser = User::factory()->create([
            'role' => 'seller',
            'age_verified' => true,
        ]);

        SellerProfile::factory()->create([
            'user_id' => $sellerUser->id,
            'verification_status' => 'approved',
        ]);

        $response = $this->actingAs($sellerUser, 'sanctum')
            ->getJson(route('api.v1.admin.analytics'));

        $response->assertStatus(403);
    }

    public function test_seller_can_access_seller_portal(): void
    {
        $sellerUser = User::factory()->create([
            'role' => 'seller',
            'age_verified' => true,
        ]);

        SellerProfile::factory()->create([
            'user_id' => $sellerUser->id,
            'verification_status' => 'approved',
        ]);

        $response = $this->actingAs($sellerUser, 'sanctum')
            ->getJson(route('api.v1.seller.dashboard'));

        $response->assertStatus(200)
            ->assertJsonStructure(['seller', 'stats', 'low_stock_alerts']);
    }

    public function test_admin_can_access_both_seller_and_admin_portals(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'age_verified' => true,
        ]);

        // Access Admin Portal
        $adminResponse = $this->actingAs($admin, 'sanctum')
            ->getJson(route('api.v1.admin.analytics'));

        $adminResponse->assertStatus(200)
            ->assertJsonStructure(['overview', 'recent_orders']);

        // Access Seller Portal
        $sellerResponse = $this->actingAs($admin, 'sanctum')
            ->getJson(route('api.v1.seller.dashboard'));

        $sellerResponse->assertStatus(200)
            ->assertJsonStructure(['seller', 'stats']);
    }

    public function test_unauthenticated_request_is_rejected_from_protected_portals(): void
    {
        $this->getJson(route('api.v1.seller.dashboard'))->assertStatus(401);
        $this->getJson(route('api.v1.admin.analytics'))->assertStatus(401);
    }
}
