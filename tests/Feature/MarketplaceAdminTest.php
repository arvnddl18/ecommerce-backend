<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_sellers_and_approve_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        $pendingSeller = SellerProfile::factory()->pending()->create();

        $listResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson(route('api.v1.admin.sellers.index', ['status' => 'pending']));

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $approveResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->putJson(route('api.v1.admin.sellers.status', $pendingSeller), [
                'status' => 'approved',
            ]);

        $approveResponse->assertStatus(200)
            ->assertJsonPath('seller.verification_status', 'approved');

        $this->assertDatabaseHas('seller_profiles', [
            'id' => $pendingSeller->id,
            'verification_status' => 'approved',
        ]);
    }

    public function test_admin_can_view_platform_analytics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        Order::factory()->count(3)->create([
            'status' => 'paid',
            'total_amount' => 15000,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson(route('api.v1.admin.analytics'));

        $response->assertStatus(200)
            ->assertJsonPath('overview.total_gmv', 45000)
            ->assertJsonPath('overview.total_orders', 3);
    }

    public function test_non_admin_cannot_access_admin_portal(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $token = $buyer->createToken('buyer_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.v1.admin.analytics'));

        $response->assertStatus(403);
    }
}
