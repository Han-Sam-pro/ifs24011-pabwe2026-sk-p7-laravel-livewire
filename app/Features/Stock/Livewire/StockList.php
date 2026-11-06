<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Stok Barang - Inventaris')]
class StockList extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public $quantity = 0;

    public string $unit = 'pcs';

    public $price = '';

    public $minStock = 5;

    public ?int $confirmingDeletionId = null;

    public bool $showImportModal = false;

    public $importFile = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    public bool $showAiModal = false;

    public string $aiPrompt = '';

    public $aiCount = 3;

    /** @var array<int, array<string, mixed>> */
    public array $aiItems = [];

    /** @var array<int, int|string> */
    public array $aiSelected = [];

    public bool $aiLoading = false;

    public string $aiError = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $stock = Stock::findOrFail($id);
        Gate::authorize('update', $stock);

        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = (string) $stock->sku;
        $this->description = (string) $stock->description;
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price !== null ? (string) $stock->price : '';
        $this->minStock = $stock->min_stock;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->sku = '';
        $this->description = '';
        $this->quantity = 0;
        $this->unit = 'pcs';
        $this->price = '';
        $this->minStock = 5;
    }

    public function saveStock(StockService $service): void
    {
        $validated = $this->validate();

        $payload = [
            'name' => $validated['name'],
            'sku' => $validated['sku'] !== '' ? $validated['sku'] : null,
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'quantity' => (int) $validated['quantity'],
            'unit' => $validated['unit'],
            'price' => $validated['price'] !== '' && $validated['price'] !== null ? (float) $validated['price'] : null,
            'min_stock' => (int) $validated['minStock'],
        ];

        if ($this->editingId !== null) {
            $stock = Stock::findOrFail($this->editingId);
            Gate::authorize('update', $stock);
            $service->updateStock($stock, $payload);
            session()->flash('status', 'Stok berhasil diperbarui.');
        } else {
            $service->createStock((int) Auth::id(), $payload);
            session()->flash('status', 'Stok baru berhasil ditambahkan.');
        }

        $this->closeFormModal();
    }

    public function adjustQuantity(int $id, int $delta, StockService $service): void
    {
        $stock = Stock::findOrFail($id);
        Gate::authorize('update', $stock);

        try {
            $service->adjustQuantity($stock, $delta);
            session()->flash('status', 'Jumlah stok diperbarui.');
        } catch (ValidationException $exception) {
            $this->addError('adjust', $exception->validator->errors()->first('quantity'));
        }
    }

    public function confirmDelete(int $id): void
    {
        $stock = Stock::findOrFail($id);
        Gate::authorize('delete', $stock);
        $this->confirmingDeletionId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeletionId = null;
    }

    public function deleteStock(StockService $service): void
    {
        if ($this->confirmingDeletionId === null) {
            return;
        }

        $stock = Stock::findOrFail($this->confirmingDeletionId);
        Gate::authorize('delete', $stock);
        $service->deleteStock($stock);

        $this->confirmingDeletionId = null;
        $this->resetPage();
        session()->flash('status', 'Stok berhasil dihapus.');
    }

    public function openImportModal(): void
    {
        $this->resetValidation();
        $this->importFile = null;
        $this->importErrors = [];
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importErrors = [];
        $this->resetValidation();
    }

    public function importExcel(StockExcelService $excel): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [], ['importFile' => 'file']);

        $result = $excel->import($this->importFile->getRealPath(), (int) Auth::id());

        $this->importErrors = $result['errors'];
        session()->flash('status', "Berhasil mengimpor {$result['imported']} stok.");

        if ($result['errors'] === []) {
            $this->closeImportModal();
        }

        $this->resetPage();
    }

    public function openAiModal(): void
    {
        $this->resetValidation();
        $this->aiPrompt = '';
        $this->aiCount = 3;
        $this->aiItems = [];
        $this->aiSelected = [];
        $this->aiError = '';
        $this->showAiModal = true;
    }

    public function closeAiModal(): void
    {
        $this->showAiModal = false;
        $this->aiItems = [];
        $this->aiSelected = [];
        $this->aiError = '';
    }

    public function generateWithAi(AiClientInterface $ai): void
    {
        $validated = $this->validate([
            'aiPrompt' => ['required', 'string', 'min:5', 'max:500'],
            'aiCount' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $this->aiError = '';
        $this->aiLoading = true;

        try {
            $items = $ai->generateStocks($validated['aiPrompt'], (int) $validated['aiCount']);
            $this->aiItems = array_map(fn ($item) => $item->toArray(), $items);
            $this->aiSelected = array_keys($this->aiItems);
        } catch (\RuntimeException $exception) {
            $this->aiError = $exception->getMessage();
            $this->aiItems = [];
            $this->aiSelected = [];
        } finally {
            $this->aiLoading = false;
        }
    }

    public function saveAiGenerated(StockService $service): void
    {
        if ($this->aiSelected === []) {
            $this->addError('aiSelected', 'Pilih minimal satu item untuk disimpan.');

            return;
        }

        $created = 0;

        foreach ($this->aiItems as $index => $item) {
            if (! in_array($index, array_map('intval', $this->aiSelected), true)) {
                continue;
            }

            $service->createStock((int) Auth::id(), $item);
            $created++;
        }

        $this->closeAiModal();
        session()->flash('status', "Berhasil menambahkan {$created} stok dari AI.");
    }

    public function exportExcel(StockExcelService $excel, StockRepositoryInterface $repository): StreamedResponse
    {
        return $excel->exportToResponse(
            $repository->allForUser((int) Auth::id()),
            'stok-'.now()->format('Ymd-His').'.xlsx'
        );
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

    public function render(StockService $service)
    {
        $userId = (int) Auth::id();

        $stocks = $service->getStocksForUser(
            $userId,
            $this->statusFilter !== '' ? $this->statusFilter : null,
            $this->search !== '' ? $this->search : null,
            8,
        );

        $counts = [
            'total' => Stock::query()->where('user_id', $userId)->count(),
            'low' => Stock::query()->where('user_id', $userId)->where('status', StockStatus::LOW_STOCK->value)->count(),
            'out' => Stock::query()->where('user_id', $userId)->where('status', StockStatus::OUT_OF_STOCK->value)->count(),
        ];

        return view('livewire.stock.stock-list', [
            'stocks' => $stocks,
            'counts' => $counts,
            'statuses' => StockStatus::cases(),
        ]);
    }
}
