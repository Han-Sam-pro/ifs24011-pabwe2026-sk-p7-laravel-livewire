<?php

namespace Tests\Unit;

use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\TestCase;

class StockStatusTest extends TestCase
{
    public function test_calculate_returns_out_of_stock_when_quantity_is_zero(): void
    {
        $this->assertSame(StockStatus::OUT_OF_STOCK, StockStatus::calculate(0, 10));
        $this->assertSame(StockStatus::OUT_OF_STOCK, StockStatus::calculate(0, 0));
    }

    public function test_calculate_returns_low_stock_when_quantity_at_or_below_minimum(): void
    {
        $this->assertSame(StockStatus::LOW_STOCK, StockStatus::calculate(3, 5));
        $this->assertSame(StockStatus::LOW_STOCK, StockStatus::calculate(5, 5));
    }

    public function test_calculate_returns_available_when_quantity_above_minimum(): void
    {
        $this->assertSame(StockStatus::AVAILABLE, StockStatus::calculate(6, 5));
        $this->assertSame(StockStatus::AVAILABLE, StockStatus::calculate(100, 0));
    }

    public function test_labels_and_badge_classes_are_defined(): void
    {
        $this->assertSame('Tersedia', StockStatus::AVAILABLE->label());
        $this->assertSame('Stok Menipis', StockStatus::LOW_STOCK->label());
        $this->assertSame('Stok Habis', StockStatus::OUT_OF_STOCK->label());

        $this->assertStringContainsString('emerald', StockStatus::AVAILABLE->badgeClass());
        $this->assertStringContainsString('amber', StockStatus::LOW_STOCK->badgeClass());
        $this->assertStringContainsString('rose', StockStatus::OUT_OF_STOCK->badgeClass());

        $this->assertStringContainsString('emerald', StockStatus::AVAILABLE->dotClass());
        $this->assertStringContainsString('amber', StockStatus::LOW_STOCK->dotClass());
        $this->assertStringContainsString('rose', StockStatus::OUT_OF_STOCK->dotClass());
    }
}
