<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->pm = User::factory()->create(['role' => 'pm']);
        $this->team = User::factory()->create(['role' => 'team']);
        $this->client = User::factory()->create(['role' => 'client']);
    }

    public function test_admin_can_view_all_users()
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(200)
                ->assertJsonCount(4); // admin, pm, team, client
    }

    public function test_pm_cannot_access_admin_users_endpoint()
    {
        $token = $this->pm->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_team_cannot_access_admin_users_endpoint()
    {
        $token = $this->team->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_client_cannot_access_admin_users_endpoint()
    {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_user()
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'team',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/users', $userData);

        $response->assertStatus(201)
                ->assertJson([
                    'name' => 'New User',
                    'email' => 'newuser@example.com',
                    'role' => 'team',
                ]);

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'team',
        ]);
    }

    public function test_pm_cannot_create_new_user()
    {
        $token = $this->pm->createToken('test-token')->plainTextToken;

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'team',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/users', $userData);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_user()
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/admin/users/{$this->team->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->team->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_admin_can_delete_user()
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/admin/users/{$this->team->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'User deleted']);

        $this->assertDatabaseMissing('users', [
            'id' => $this->team->id,
        ]);
    }

    public function test_pm_cannot_delete_user()
    {
        $token = $this->pm->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/admin/users/{$this->team->id}");

        $response->assertStatus(403);
    }

    public function test_user_creation_validation()
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/users', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_user_update_validation()
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/admin/users/{$this->team->id}", [
            'email' => 'invalid-email',
            'role' => 'invalid-role',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'role']);
    }

    public function test_cannot_create_user_with_duplicate_email()
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $userData = [
            'name' => 'New User',
            'email' => $this->pm->email, // Using existing email
            'password' => 'password123',
            'role' => 'team',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/users', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_user_cannot_access_admin_endpoints()
    {
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/users', []);
        $response->assertStatus(401);

        $response = $this->putJson("/api/admin/users/{$this->team->id}", []);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/admin/users/{$this->team->id}");
        $response->assertStatus(401);
    }
}
