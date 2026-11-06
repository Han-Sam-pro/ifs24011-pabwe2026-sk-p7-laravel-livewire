<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Fields that may be mass assigned from user input.
     */
    private const EDITABLE_FIELDS = [
        'name',
        'sku',
        'description',
        'quantity',
        'unit',
        'price',
        'min_stock',
    ];

    public function __construct(private readonly StockRepositoryInterface $repository) {}

    public function getStocksForUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 8): LengthAwarePaginator
    {
        return $this->repository->forUser($userId, $status, $search, $perPage);
    }

    public function getStock(int $id): ?Stock
    {
        return $this->repository->find($id);
    }

    /**
     * Create a stock row. The status is always derived, never taken from input.
     */
    public function createStock(int $userId, array $data): Stock
    {
        $attributes = Arr::only($data, self::EDITABLE_FIELDS);
        $attributes['user_id'] = $userId;
        $attributes['quantity'] = (int) ($attributes['quantity'] ?? 0);
        $attributes['unit'] = $attributes['unit'] ?? 'pcs';
        $attributes['min_stock'] = (int) ($attributes['min_stock'] ?? 5);
        $attributes['status'] = $this->computeStatus($attributes['quantity'], $attributes['min_stock'])->value;

        return $this->repository->create($attributes);
    }

    /**
     * Update editable fields and recompute the status from the resulting quantity and min stock.
     */
    public function updateStock(Stock $stock, array $data): bool
    {
        $attributes = Arr::only($data, self::EDITABLE_FIELDS);

        $quantity = array_key_exists('quantity', $attributes)
            ? (int) $attributes['quantity']
            : $stock->quantity;

        $minStock = array_key_exists('min_stock', $attributes)
            ? (int) $attributes['min_stock']
            : $stock->min_stock;

        $attributes['status'] = $this->computeStatus($quantity, $minStock)->value;

        return $this->repository->update($stock, $attributes);
    }

    /**
     * Adjust the quantity by a positive or negative delta and sync the status.
     *
     * @throws ValidationException when the resulting quantity would be negative
     */
    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $newQuantity = $stock->quantity + $delta;

        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok tidak boleh minus.',
            ]);
        }

        $stock->quantity = $newQuantity;
        $stock->status = $this->computeStatus($newQuantity, $stock->min_stock);
        $stock->save();

        return $stock->refresh();
    }

    /**
     * Replace the cover image and remove the previous file from disk.
     */
    public function updateCoverPhoto(Stock $stock, mixed $file): string
    {
        $this->deleteStoredCover($stock);

        $path = $file->store('stock-covers', 'public');

        $stock->update(['cover_image_path' => $path]);

        return $path;
    }

    public function deleteCoverPhoto(Stock $stock): bool
    {
        $this->deleteStoredCover($stock);

        return $stock->update(['cover_image_path' => null]);
    }

    public function deleteStock(Stock $stock): bool
    {
        $this->deleteStoredCover($stock);

        return $this->repository->delete($stock);
    }

    /**
     * The one and only status calculator used by forms, adjust, import and AI.
     */
    public function computeStatus(int $quantity, int $minStock): StockStatus
    {
        return StockStatus::calculate($quantity, $minStock);
    }

    private function deleteStoredCover(Stock $stock): void
    {
        if ($stock->cover_image_path && Storage::disk('public')->exists($stock->cover_image_path)) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }
}
