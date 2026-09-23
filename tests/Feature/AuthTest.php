<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Alice Engineer',
            'email' => 'alice@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'created_at'],
                'token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
        ]);
    }

    public function test_registration_requires_valid_data(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'bob@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'bob@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_retrieve_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.v1.auth.user'));

        $profileResponse->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.v1.auth.logout'));

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('message', 'Successfully logged out.');
    }
}
