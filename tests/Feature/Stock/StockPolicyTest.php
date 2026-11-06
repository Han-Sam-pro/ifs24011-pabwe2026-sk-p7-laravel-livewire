<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    use RefreshDatabase;

    private StockPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new StockPolicy;
    }

    public function test_view_any_and_create_are_allowed_for_everyone(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->create($user));
    }

    public function test_view_allows_owner_and_admin_only(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->for($owner)->create();

        $this->assertTrue($this->policy->view($owner, $stock));
        $this->assertTrue($this->policy->view($admin, $stock));
        $this->assertFalse($this->policy->view(User::factory()->create(), $stock));
    }

    public function test_update_delete_and_adjust_require_ownership(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->for($owner)->create();

        $this->assertTrue($this->policy->update($owner, $stock));
        $this->assertTrue($this->policy->delete($owner, $stock));
        $this->assertTrue($this->policy->adjust($owner, $stock));

        $this->assertFalse($this->policy->update($admin, $stock));
        $this->assertFalse($this->policy->delete($admin, $stock));
        $this->assertFalse($this->policy->adjust($admin, $stock));
    }
}
