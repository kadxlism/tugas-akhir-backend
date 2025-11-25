<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoleMiddleware();
    }

    public function test_middleware_allows_access_for_correct_role()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Auth::login($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        }, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_denies_access_for_incorrect_role()
    {
        $user = User::factory()->create(['role' => 'user']);
        Auth::login($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        }, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    public function test_middleware_denies_access_for_unauthenticated_user()
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        }, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    public function test_middleware_works_with_different_roles()
    {
        $roles = ['admin', 'pm', 'team', 'client'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            Auth::login($user);

            $request = Request::create('/test', 'GET');
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            }, $role);

            $this->assertEquals(200, $response->getStatusCode(), "Failed for role: {$role}");
        }
    }

    public function test_middleware_preserves_request_data()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Auth::login($user);

        $request = Request::create('/test', 'POST', ['data' => 'test']);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response($req->input('data'));
        }, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test', $response->getContent());
    }
}
