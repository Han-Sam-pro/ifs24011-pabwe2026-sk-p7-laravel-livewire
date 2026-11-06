<?php

namespace App\Features\Stock\Contracts;

use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockRepositoryInterface
{
    /**
     * Paginate stocks that belong to a user with optional status and search filters.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 8): LengthAwarePaginator;

    /**
     * All stocks of a user, newest first.
     *
     * @return Collection<int, Stock>
     */
    public function allForUser(int $userId): Collection;

    public function find(int $id): ?Stock;

    public function findOrFail(int $id): Stock;

    public function create(array $data): Stock;

    public function update(Stock $stock, array $data): bool;

    public function delete(Stock $stock): bool;

    /**
     * Insert many stock rows in one query.
     */
    public function insertMany(array $rows): bool;
}
