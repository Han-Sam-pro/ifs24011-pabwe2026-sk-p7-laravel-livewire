<?php

namespace App\Features\Ai\DTO;

/**
 * One stock item produced by the AI, before it is validated and persisted.
 */
class GeneratedStockItem
{
    public function __construct(
        public string $name,
        public ?string $sku = null,
        public ?string $description = null,
        public int $quantity = 0,
        public string $unit = 'pcs',
        public ?float $price = null,
        public int $minStock = 5,
    ) {}

    /**
     * @return array{name: string, sku: ?string, description: ?string, quantity: int, unit: string, price: ?float, min_stock: int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
        ];
    }
}
