<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function forUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 8): LengthAwarePaginator
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function allForUser(int $userId): Collection
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get();
    }

    public function find(int $id): ?Stock
    {
        return Stock::find($id);
    }

    public function findOrFail(int $id): Stock
    {
        return Stock::findOrFail($id);
    }

    public function create(array $data): Stock
    {
        return Stock::create($data);
    }

    public function update(Stock $stock, array $data): bool
    {
        return $stock->update($data);
    }

    public function delete(Stock $stock): bool
    {
        return (bool) $stock->delete();
    }

    public function insertMany(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        return Stock::insert($rows);
    }
}
