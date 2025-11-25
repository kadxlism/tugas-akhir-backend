<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    public function test_admin_can_view_any_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_pm_can_view_any_users()
    {
        $pm = User::factory()->create(['role' => 'pm']);
        $this->assertTrue($this->policy->viewAny($pm));
    }

    public function test_team_cannot_view_any_users()
    {
        $team = User::factory()->create(['role' => 'team']);
        $this->assertFalse($this->policy->viewAny($team));
    }

    public function test_client_cannot_view_any_users()
    {
        $client = User::factory()->create(['role' => 'client']);
        $this->assertFalse($this->policy->viewAny($client));
    }

    public function test_user_can_view_own_profile()
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->view($user, $user));
    }

    public function test_admin_can_view_other_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->view($admin, $otherUser));
    }

    public function test_pm_can_view_other_users()
    {
        $pm = User::factory()->create(['role' => 'pm']);
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->view($pm, $otherUser));
    }

    public function test_team_cannot_view_other_users()
    {
        $team = User::factory()->create(['role' => 'team']);
        $otherUser = User::factory()->create();
        $this->assertFalse($this->policy->view($team, $otherUser));
    }

    public function test_only_admin_can_create_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pm = User::factory()->create(['role' => 'pm']);
        $team = User::factory()->create(['role' => 'team']);
        $client = User::factory()->create(['role' => 'client']);

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($pm));
        $this->assertFalse($this->policy->create($team));
        $this->assertFalse($this->policy->create($client));
    }

    public function test_user_can_update_own_profile()
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->update($user, $user));
    }

    public function test_admin_can_update_other_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->update($admin, $otherUser));
    }

    public function test_non_admin_cannot_update_other_users()
    {
        $pm = User::factory()->create(['role' => 'pm']);
        $otherUser = User::factory()->create();
        $this->assertFalse($this->policy->update($pm, $otherUser));
    }

    public function test_only_admin_can_delete_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pm = User::factory()->create(['role' => 'pm']);
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $otherUser));
        $this->assertFalse($this->policy->delete($pm, $otherUser));
    }

    public function test_admin_cannot_delete_themselves()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    public function test_only_admin_can_restore_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pm = User::factory()->create(['role' => 'pm']);
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->restore($admin, $otherUser));
        $this->assertFalse($this->policy->restore($pm, $otherUser));
    }

    public function test_only_admin_can_force_delete_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pm = User::factory()->create(['role' => 'pm']);
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->forceDelete($admin, $otherUser));
        $this->assertFalse($this->policy->forceDelete($pm, $otherUser));
    }

    public function test_admin_cannot_force_delete_themselves()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertFalse($this->policy->forceDelete($admin, $admin));
    }
}
