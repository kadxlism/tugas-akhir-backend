<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('admin', $user->role);
    }

    public function test_user_password_is_hashed()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_user_role_validation()
    {
        $validRoles = ['admin', 'pm', 'team', 'client'];
        
        foreach ($validRoles as $role) {
            $user = User::create([
                'name' => 'Test User',
                'email' => "test{$role}@example.com",
                'password' => Hash::make('password'),
                'role' => $role,
            ]);

            $this->assertEquals($role, $user->role);
        }
    }

    public function test_user_has_projects_relationship()
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->projects());
    }

    public function test_user_can_create_api_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        
        $this->assertInstanceOf(\Laravel\Sanctum\NewAccessToken::class, $token);
        $this->assertNotEmpty($token->plainTextToken);
    }

    public function test_user_can_have_multiple_tokens()
    {
        $user = User::factory()->create();
        
        $token1 = $user->createToken('token-1');
        $token2 = $user->createToken('token-2');
        
        $this->assertCount(2, $user->tokens);
    }

    public function test_user_password_is_hidden_in_serialization()
    {
        $user = User::factory()->create();
        $userArray = $user->toArray();
        
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    public function test_user_email_verification_cast()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }

    public function test_user_password_cast()
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);
        
        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
