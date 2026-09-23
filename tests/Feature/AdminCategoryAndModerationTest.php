<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCategoryAndModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_hierarchical_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Create parent category
        $parentRes = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Apparel',
            'slug' => 'apparel',
            'description' => 'Main apparel category',
        ]);
        $parentRes->assertStatus(201);
        $parentId = $parentRes->json('category.id');

        // Create nested sub-category
        $subRes = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Trousers',
            'parent_id' => $parentId,
        ]);
        $subRes->assertStatus(201)
            ->assertJsonPath('category.parent_id', $parentId);

        $this->assertDatabaseHas('categories', [
            'name' => 'Trousers',
            'parent_id' => $parentId,
        ]);
    }

    public function test_admin_can_update_and_delete_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $category = Category::create([
            'name' => 'Legacy Headwear',
            'slug' => 'legacy-headwear',
        ]);

        $updateRes = $this->putJson("/api/v1/admin/categories/{$category->id}", [
            'name' => 'Caps & Beanies',
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('category.name', 'Caps & Beanies');

        $deleteRes = $this->deleteJson("/api/v1/admin/categories/{$category->id}");
        $deleteRes->assertStatus(200);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_can_ban_and_unban_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $badUser = User::factory()->create(['role' => 'buyer', 'is_banned' => false]);

        Sanctum::actingAs($admin);

        // Ban user
        $banRes = $this->putJson("/api/v1/admin/users/{$badUser->id}/ban", [
            'is_banned' => true,
        ]);
        $banRes->assertStatus(200)
            ->assertJsonPath('user.is_banned', true);

        $this->assertDatabaseHas('users', [
            'id' => $badUser->id,
            'is_banned' => true,
        ]);

        // Unban user
        $unbanRes = $this->putJson("/api/v1/admin/users/{$badUser->id}/ban", [
            'is_banned' => false,
        ]);
        $unbanRes->assertStatus(200)
            ->assertJsonPath('user.is_banned', false);
    }

    public function test_non_admin_cannot_manage_categories_or_ban_users(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $targetUser = User::factory()->create(['role' => 'buyer']);
        $category = Category::create(['name' => 'Footwear', 'slug' => 'footwear']);

        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/admin/categories', ['name' => 'Sneakers'])->assertStatus(403);
        $this->putJson("/api/v1/admin/categories/{$category->id}", ['name' => 'Boots'])->assertStatus(403);
        $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertStatus(403);
        $this->putJson("/api/v1/admin/users/{$targetUser->id}/ban", ['is_banned' => true])->assertStatus(403);
    }
}
