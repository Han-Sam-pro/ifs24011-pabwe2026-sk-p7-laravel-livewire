<?php

namespace App\Features\Stock\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Stock extends Model
{
    /** @use HasFactory<\Database\Factories\StockFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'sku',
        'description',
        'quantity',
        'unit',
        'price',
        'min_stock',
        'status',
        'cover_image_path',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'min_stock' => 'integer',
            'status' => StockStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public URL of the cover image, or null when there is no cover.
     */
    public function coverImageUrl(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->cover_image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }

    /**
     * Formatted price for display.
     */
    public function formattedPrice(): string
    {
        if ($this->price === null) {
            return '-';
        }

        return 'Rp '.number_format((float) $this->price, 0, ',', '.');
    }

    protected static function newFactory(): \Database\Factories\StockFactory
    {
        return \Database\Factories\StockFactory::new();
    }
}
