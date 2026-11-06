<?php

namespace Tests\Feature\Http;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: string}
     */
    private function runOwnership(Request $request): array
    {
        try {
            $response = (new EnsureStockOwnership)->handle($request, fn () => response('ok'));

            return [$response->getStatusCode(), (string) $response->getContent()];
        } catch (HttpException $exception) {
            return [$exception->getStatusCode(), $exception->getMessage()];
        }
    }

    private function requestFor(User $user, string $uri, array $parameters, string $method = 'GET'): Request
    {
        $request = Request::create($uri, $method);
        $request->setUserResolver(fn () => $user);

        $route = new Route($method, $uri, []);
        $route->bind($request);
        foreach ($parameters as $name => $value) {
            $route->setParameter($name, $value);
        }
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    public function test_stock_ownership_requires_authenticated_user(): void
    {
        [$status] = $this->runOwnership(Request::create('/stocks/1'));

        $this->assertSame(401, $status);
    }

    public function test_user_route_blocks_member_from_another_user(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();

        $request = $this->requestFor($member, '/admin/users/'.$other->id.'/stocks', ['user' => $other->id]);
        [$status, $message] = $this->runOwnership($request);

        $this->assertSame(403, $status);
        $this->assertStringContainsString('stok pengguna lain', $message);
    }

    public function test_user_route_allows_admin_and_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        foreach ([$admin, $member] as $user) {
            $request = $this->requestFor($user, '/admin/users/'.$user->id.'/stocks', ['user' => $user->id]);

            $this->assertSame(200, $this->runOwnership($request)[0]);
        }
    }

    public function test_missing_stock_parameter_passes_through(): void
    {
        $member = User::factory()->create();

        $request = $this->requestFor($member, '/stocks', []);

        $this->assertSame(200, $this->runOwnership($request)[0]);
    }

    public function test_unsafe_method_by_non_owner_is_forbidden_even_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();

        $request = $this->requestFor($admin, '/stocks/'.$stock->id, ['stock' => $stock->id], 'POST');
        [$status, $message] = $this->runOwnership($request);

        $this->assertSame(403, $status);
        $this->assertStringContainsString('mengubah stok', $message);
    }

    public function test_owner_can_access_own_stock_page(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->for($user)->create();

        $this->actingAs($user)->get(route('stocks.show', $stock))->assertOk();
    }

    public function test_member_cannot_access_another_users_stock_page(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $stock = Stock::factory()->for($other)->create();

        $this->actingAs($user)->get(route('stocks.show', $stock))->assertForbidden();
    }

    public function test_admin_can_view_any_stock_page(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();

        $this->actingAs($admin)->get(route('stocks.show', $stock))->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/stocks')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_role_middleware_blocks_member_from_admin_area(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.users.stocks', $other))
            ->assertForbidden();
    }

    public function test_role_middleware_allows_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.users'))->assertOk();
    }

    public function test_admin_stock_page_is_read_only_and_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        Stock::factory()->for($member)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.stocks', $member))
            ->assertOk()
            ->assertSee('Mode Admin');
    }
}
