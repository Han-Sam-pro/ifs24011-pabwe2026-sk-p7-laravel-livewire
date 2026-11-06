<?php

namespace Tests\Unit;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_defaults_are_applied(): void
    {
        $item = new GeneratedStockItem(name: 'Kertas A4');

        $this->assertSame(0, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertSame(5, $item->minStock);
        $this->assertNull($item->price);
        $this->assertNull($item->sku);
        $this->assertNull($item->description);
    }

    public function test_to_array_uses_snake_case_min_stock(): void
    {
        $item = new GeneratedStockItem(
            name: 'Tinta',
            sku: 'TNK-01',
            description: 'Tinta printer',
            quantity: 4,
            unit: 'botol',
            price: 55000.5,
            minStock: 2,
        );

        $this->assertSame([
            'name' => 'Tinta',
            'sku' => 'TNK-01',
            'description' => 'Tinta printer',
            'quantity' => 4,
            'unit' => 'botol',
            'price' => 55000.5,
            'min_stock' => 2,
        ], $item->toArray());
    }
}
