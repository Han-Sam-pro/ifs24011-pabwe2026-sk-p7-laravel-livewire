<?php

namespace Database\Factories;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(0, 100);
        $minStock = fake()->numberBetween(3, 15);

        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-###-??')),
            'description' => fake()->paragraph(),
            'quantity' => $quantity,
            'unit' => 'pcs',
            'price' => fake()->randomFloat(2, 1000, 250000),
            'min_stock' => $minStock,
            'status' => StockStatus::calculate($quantity, $minStock)->value,
            'cover_image_path' => null,
        ];
    }

    /**
     * A stock that is available.
     */
    public function available(): static
    {
        return $this->state(fn () => [
            'quantity' => 50,
            'min_stock' => 5,
            'status' => StockStatus::AVAILABLE->value,
        ]);
    }

    /**
     * A stock whose quantity is at or below the minimum.
     */
    public function lowStock(): static
    {
        return $this->state(fn () => [
            'quantity' => 3,
            'min_stock' => 5,
            'status' => StockStatus::LOW_STOCK->value,
        ]);
    }

    /**
     * A stock with zero quantity.
     */
    public function outOfStock(): static
    {
        return $this->state(fn () => [
            'quantity' => 0,
            'min_stock' => 5,
            'status' => StockStatus::OUT_OF_STOCK->value,
        ]);
    }
}
