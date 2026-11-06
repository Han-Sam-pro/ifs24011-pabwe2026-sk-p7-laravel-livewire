<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private StockRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(StockRepositoryInterface::class);
    }

    public function test_for_user_returns_only_owned_and_paginates(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Stock::factory()->for($user)->count(3)->create();
        Stock::factory()->for($other)->create();

        $paginator = $this->repository->forUser($user->id, null, null, 2);

        $this->assertSame(3, $paginator->total());
        $this->assertCount(2, $paginator->items());
        $this->assertSame(2, $paginator->perPage());
    }

    public function test_for_user_filters_by_status_and_search_on_name_or_sku(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->lowStock()->create(['name' => 'Tinta Printer', 'sku' => 'TNK-1']);
        Stock::factory()->for($user)->available()->create(['name' => 'Kertas', 'sku' => 'KRS-1']);

        $this->assertCount(1, $this->repository->forUser($user->id, StockStatus::LOW_STOCK->value));
        $this->assertCount(1, $this->repository->forUser($user->id, null, 'Tinta'));
        $this->assertCount(1, $this->repository->forUser($user->id, null, 'KRS-1'));
        $this->assertCount(0, $this->repository->forUser($user->id, null, 'zzz'));
    }

    public function test_all_for_user_returns_latest_first(): void
    {
        $user = User::factory()->create();
        $first = Stock::factory()->for($user)->create();
        $second = Stock::factory()->for($user)->create();

        $all = $this->repository->allForUser($user->id);

        $this->assertCount(2, $all);
        $this->assertTrue($all->first()->is($second));
        $this->assertTrue($all->last()->is($first));
    }

    public function test_find_and_find_or_fail(): void
    {
        $stock = Stock::factory()->create();

        $this->assertTrue($this->repository->find($stock->id)->is($stock));
        $this->assertNull($this->repository->find(9999));

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->findOrFail(9999);
    }

    public function test_create_update_and_delete(): void
    {
        $user = User::factory()->create();

        $stock = $this->repository->create([
            'user_id' => $user->id,
            'name' => 'Map',
            'quantity' => 1,
            'unit' => 'pcs',
            'min_stock' => 5,
            'status' => StockStatus::LOW_STOCK->value,
        ]);

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Map']);

        $this->assertTrue($this->repository->update($stock, ['name' => 'Map Plastik']));
        $this->assertSame('Map Plastik', $stock->fresh()->name);

        $this->assertTrue($this->repository->delete($stock));
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_insert_many_handles_empty_and_non_empty_rows(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->repository->insertMany([]));

        $this->assertTrue($this->repository->insertMany([
            [
                'user_id' => $user->id,
                'name' => 'Barang A',
                'quantity' => 10,
                'unit' => 'pcs',
                'min_stock' => 5,
                'status' => StockStatus::AVAILABLE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'name' => 'Barang B',
                'quantity' => 0,
                'unit' => 'pcs',
                'min_stock' => 5,
                'status' => StockStatus::OUT_OF_STOCK->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]));

        $this->assertDatabaseCount('stocks', 2);
    }
}
