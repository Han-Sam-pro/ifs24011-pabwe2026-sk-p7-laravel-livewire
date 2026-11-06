<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class StockDetail extends Component
{
    use WithFileUploads;

    public Stock $stock;

    public bool $showEditModal = false;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public $quantity = 0;

    public string $unit = 'pcs';

    public $price = '';

    public $minStock = 5;

    public $coverPhoto = null;

    public bool $confirmingCoverDeletion = false;

    public bool $confirmingDeletion = false;

    public function mount(Stock $stock): void
    {
        Gate::authorize('view', $stock);

        $this->stock = $stock;
        $this->fillForm();
    }

    public function fillForm(): void
    {
        $this->name = $this->stock->name;
        $this->sku = (string) $this->stock->sku;
        $this->description = (string) $this->stock->description;
        $this->quantity = $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price !== null ? (string) $this->stock->price : '';
        $this->minStock = $this->stock->min_stock;
    }

    public function openEditModal(): void
    {
        Gate::authorize('update', $this->stock);
        $this->fillForm();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->fillForm();
        $this->resetValidation();
    }

    public function saveStock(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $validated = $this->validate();

        $service->updateStock($this->stock, [
            'name' => $validated['name'],
            'sku' => ($validated['sku'] ?? '') !== '' ? $validated['sku'] : null,
            'description' => ($validated['description'] ?? '') !== '' ? $validated['description'] : null,
            'quantity' => (int) $validated['quantity'],
            'unit' => $validated['unit'],
            'price' => ($validated['price'] ?? '') !== '' && $validated['price'] !== null ? (float) $validated['price'] : null,
            'min_stock' => (int) $validated['minStock'],
        ]);

        $this->stock->refresh();
        $this->showEditModal = false;
        session()->flash('status', 'Stok berhasil diperbarui.');
    }

    public function adjustQuantity(int $delta, StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        try {
            $this->stock = $service->adjustQuantity($this->stock, $delta);
            $this->fillForm();
            session()->flash('status', 'Jumlah stok diperbarui.');
        } catch (ValidationException $exception) {
            $this->addError('adjust', $exception->validator->errors()->first('quantity'));
        }
    }

    public function updatedCoverPhoto(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $this->validate([
            'coverPhoto' => ['image', 'max:5120'],
        ], [], ['coverPhoto' => 'foto cover']);

        $service->updateCoverPhoto($this->stock, $this->coverPhoto);
        $this->stock->refresh();
        $this->coverPhoto = null;
        session()->flash('status', 'Foto cover berhasil diperbarui.');
    }

    public function confirmCoverDeletion(): void
    {
        Gate::authorize('update', $this->stock);
        $this->confirmingCoverDeletion = true;
    }

    public function cancelCoverDeletion(): void
    {
        $this->confirmingCoverDeletion = false;
    }

    public function deleteCoverPhoto(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $service->deleteCoverPhoto($this->stock);
        $this->stock->refresh();
        $this->confirmingCoverDeletion = false;
        session()->flash('status', 'Foto cover berhasil dihapus.');
    }

    public function confirmDeletion(): void
    {
        Gate::authorize('delete', $this->stock);
        $this->confirmingDeletion = true;
    }

    public function cancelDeletion(): void
    {
        $this->confirmingDeletion = false;
    }

    public function deleteStock(StockService $service)
    {
        Gate::authorize('delete', $this->stock);

        $service->deleteStock($this->stock);
        session()->flash('status', 'Stok berhasil dihapus.');

        return redirect()->route('stocks.index');
    }

    public function render()
    {
        return view('livewire.stock.stock-detail')
            ->title($this->stock->name.' - Inventaris');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:10000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'minStock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nama',
            'sku' => 'SKU',
            'quantity' => 'jumlah',
            'unit' => 'satuan',
            'price' => 'harga',
            'minStock' => 'minimal stok',
        ];
    }
}
