<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(private readonly StockRepositoryInterface $stockRepository) {}

    /**
     * Paginate users with their stock totals and low/out of stock counts.
     */
    public function getUsersList(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount([
                'stocks',
                'stocks as low_stock_count' => fn ($query) => $query->where('status', StockStatus::LOW_STOCK->value),
                'stocks as out_of_stock_count' => fn ($query) => $query->where('status', StockStatus::OUT_OF_STOCK->value),
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('username', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function getUserStocks(int $userId, ?string $status = null, ?string $search = null, int $perPage = 8): LengthAwarePaginator
    {
        return $this->stockRepository->forUser($userId, $status, $search, $perPage);
    }
}
