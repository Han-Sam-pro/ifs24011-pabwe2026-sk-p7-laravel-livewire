<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StockService::class);
    }

    public function test_create_stock_applies_defaults_and_computes_status(): void
    {
        $user = User::factory()->create();

        $stock = $this->service->createStock($user->id, [
            'name' => 'Kertas HVS',
            'sku' => null,
            'description' => null,
            'quantity' => 0,
            'unit' => 'rim',
            'price' => 55000,
            'min_stock' => 5,
            'status' => StockStatus::AVAILABLE->value,
        ]);

        $this->assertSame($user->id, $stock->user_id);
        $this->assertSame('Kertas HVS', $stock->name);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame('rim', $stock->unit);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame(StockStatus::OUT_OF_STOCK, $stock->status);
    }

    public function test_create_stock_uses_fallbacks_when_optional_values_are_missing(): void
    {
        $user = User::factory()->create();

        $stock = $this->service->createStock($user->id, ['name' => 'Map']);

        $this->assertSame(0, $stock->quantity);
        $this->assertSame('pcs', $stock->unit);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame(StockStatus::OUT_OF_STOCK, $stock->status);
    }

    public function test_update_stock_recomputes_status_from_new_quantity(): void
    {
        $stock = Stock::factory()->available()->create(['quantity' => 20, 'min_stock' => 5]);

        $updated = $this->service->updateStock($stock, ['quantity' => 4]);

        $this->assertTrue($updated);
        $this->assertSame(4, $stock->refresh()->quantity);
        $this->assertSame(StockStatus::LOW_STOCK, $stock->status);
    }

    public function test_update_stock_uses_quantity_and_minimum_when_only_minimum_changes(): void
    {
        $stock = Stock::factory()->outOfStock()->create(['quantity' => 0, 'min_stock' => 5]);

        $this->service->updateStock($stock, ['min_stock' => 0, 'name' => 'Baru']);

        $this->assertSame('Baru', $stock->refresh()->name);
        $this->assertSame(StockStatus::OUT_OF_STOCK, $stock->status);
    }

    public function test_update_stock_keeps_stock_available_and_ignores_non_editable_fields(): void
    {
        $stock = Stock::factory()->available()->create(['quantity' => 30, 'min_stock' => 5]);

        $this->assertTrue($this->service->updateStock($stock, ['user_id' => 999]));
        $this->assertNotSame(999, $stock->refresh()->user_id);
        $this->assertSame(StockStatus::AVAILABLE, $stock->status);
    }

    public function test_adjust_quantity_increases_and_decreases_stock(): void
    {
        $stock = Stock::factory()->available()->create(['quantity' => 10, 'min_stock' => 5]);

        $this->service->adjustQuantity($stock, 5);
        $this->assertSame(15, $stock->refresh()->quantity);

        $this->service->adjustQuantity($stock, -10);
        $this->assertSame(5, $stock->refresh()->quantity);
        $this->assertSame(StockStatus::LOW_STOCK, $stock->status);
    }

    public function test_adjust_quantity_to_zero_marks_out_of_stock(): void
    {
        $stock = Stock::factory()->available()->create(['quantity' => 1, 'min_stock' => 5]);

        $this->service->adjustQuantity($stock, -1);

        $this->assertSame(0, $stock->quantity);
        $this->assertSame(StockStatus::OUT_OF_STOCK, $stock->status);
    }

    public function test_adjust_quantity_rejects_negative_result(): void
    {
        $stock = Stock::factory()->create(['quantity' => 2]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->adjustQuantity($stock, -5);
        } catch (ValidationException $exception) {
            $this->assertSame('Stok tidak boleh minus.', $exception->errors()['quantity'][0]);
            $this->assertSame(2, $stock->refresh()->quantity);

            throw $exception;
        }
    }

    public function test_update_cover_photo_stores_new_file_and_removes_old_one(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create(['cover_image_path' => 'stock-covers/old.jpg']);
        Storage::disk('public')->put('stock-covers/old.jpg', 'old');

        $path = $this->service->updateCoverPhoto($stock, UploadedFile::fake()->image('cover.png', 200, 100));

        $this->assertStringStartsWith('stock-covers/', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('stock-covers/old.jpg');
        $this->assertSame($path, $stock->refresh()->cover_image_path);
    }

    public function test_delete_cover_photo_removes_file_and_clears_path(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create(['cover_image_path' => 'stock-covers/cover.jpg']);
        Storage::disk('public')->put('stock-covers/cover.jpg', 'file');

        $this->assertTrue($this->service->deleteCoverPhoto($stock));

        Storage::disk('public')->assertMissing('stock-covers/cover.jpg');
        $this->assertNull($stock->refresh()->cover_image_path);
    }

    public function test_delete_cover_photo_tolerates_missing_file(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create(['cover_image_path' => 'stock-covers/missing.jpg']);

        $this->assertTrue($this->service->deleteCoverPhoto($stock));
        $this->assertNull($stock->refresh()->cover_image_path);
    }

    public function test_delete_cover_photo_with_null_path(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create(['cover_image_path' => null]);

        $this->assertTrue($this->service->deleteCoverPhoto($stock));
        $this->assertNull($stock->cover_image_path);
    }

    public function test_delete_stock_removes_cover_and_record(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create(['cover_image_path' => 'stock-covers/cover.jpg']);
        Storage::disk('public')->put('stock-covers/cover.jpg', 'file');

        $this->assertTrue($this->service->deleteStock($stock));

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        Storage::disk('public')->assertMissing('stock-covers/cover.jpg');
    }

    public function test_get_stocks_for_user_filters_by_status_and_search(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->available()->create(['name' => 'Kertas']);
        Stock::factory()->for($user)->lowStock()->create(['name' => 'Tinta', 'sku' => 'TNK']);
        Stock::factory()->for($user)->outOfStock()->create(['name' => 'Map']);

        $this->assertCount(3, $this->service->getStocksForUser($user->id));
        $this->assertCount(1, $this->service->getStocksForUser($user->id, 'low_stock'));
        $this->assertCount(1, $this->service->getStocksForUser($user->id, null, 'TNK'));
        $this->assertCount(0, $this->service->getStocksForUser($user->id, null, 'tidak-ada'));
    }

    public function test_get_stock_returns_model_or_null(): void
    {
        $stock = Stock::factory()->create();

        $this->assertTrue($this->service->getStock($stock->id)->is($stock));
        $this->assertNull($this->service->getStock(99999));
    }

    public function test_compute_status_delegates_to_enum(): void
    {
        $this->assertSame(StockStatus::OUT_OF_STOCK, $this->service->computeStatus(0, 5));
        $this->assertSame(StockStatus::LOW_STOCK, $this->service->computeStatus(5, 5));
        $this->assertSame(StockStatus::AVAILABLE, $this->service->computeStatus(6, 5));
    }
}
