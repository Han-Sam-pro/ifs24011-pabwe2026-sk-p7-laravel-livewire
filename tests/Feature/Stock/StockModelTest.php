<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_relations_and_factory_states(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->for($user)->available()->create([
            'name' => 'Kertas',
            'price' => 12345.678,
            'quantity' => '7',
            'min_stock' => '3',
        ]);

        $this->assertTrue($stock->user->is($user));
        $this->assertSame(7, $stock->quantity);
        $this->assertSame(3, $stock->min_stock);
        $this->assertSame('12345.68', $stock->price);
        $this->assertSame(StockStatus::AVAILABLE, $stock->status);

        $this->assertSame(StockStatus::LOW_STOCK, Stock::factory()->lowStock()->make()->status);
        $this->assertSame(StockStatus::OUT_OF_STOCK, Stock::factory()->outOfStock()->make()->status);
    }

    public function test_formatted_price(): void
    {
        $this->assertSame('Rp 1.234.567', Stock::factory()->make(['price' => 1234567])->formattedPrice());
        $this->assertSame('-', Stock::factory()->make(['price' => null])->formattedPrice());
    }

    public function test_cover_image_url_returns_null_without_path(): void
    {
        Storage::fake('public');

        $this->assertNull(Stock::factory()->make(['cover_image_path' => null])->coverImageUrl());
    }

    public function test_cover_image_url_returns_null_when_file_missing(): void
    {
        Storage::fake('public');

        $this->assertNull(Stock::factory()->make(['cover_image_path' => 'stock-covers/missing.jpg'])->coverImageUrl());
    }

    public function test_cover_image_url_returns_public_url_when_file_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('stock-covers/cover.jpg', 'data');

        $url = Stock::factory()->make(['cover_image_path' => 'stock-covers/cover.jpg'])->coverImageUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString('stock-covers/cover.jpg', $url);
    }
}
