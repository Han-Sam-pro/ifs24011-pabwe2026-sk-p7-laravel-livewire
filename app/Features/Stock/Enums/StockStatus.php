<?php

namespace App\Features\Stock\Enums;

enum StockStatus: string
{
    case AVAILABLE = 'available';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';

    /**
     * Calculate the status from quantity and minimum stock.
     *
     * This is the single source of truth for the stock status rule:
     * quantity 0 => out_of_stock, quantity <= min_stock => low_stock, otherwise available.
     */
    public static function calculate(int $quantity, int $minStock): self
    {
        if ($quantity === 0) {
            return self::OUT_OF_STOCK;
        }

        if ($quantity <= $minStock) {
            return self::LOW_STOCK;
        }

        return self::AVAILABLE;
    }

    /**
     * Human readable label in Indonesian.
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::LOW_STOCK => 'Stok Menipis',
            self::OUT_OF_STOCK => 'Stok Habis',
        };
    }

    /**
     * Tailwind badge classes for the status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::LOW_STOCK => 'bg-amber-100 text-amber-700 border-amber-200',
            self::OUT_OF_STOCK => 'bg-rose-100 text-rose-700 border-rose-200',
        };
    }

    /**
     * Colors used by charts and dot indicators.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-emerald-500',
            self::LOW_STOCK => 'bg-amber-500',
            self::OUT_OF_STOCK => 'bg-rose-500',
        };
    }
}
