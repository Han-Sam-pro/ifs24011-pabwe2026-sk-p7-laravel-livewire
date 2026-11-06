<?php

namespace Tests\Feature\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class StockListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function importFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['ID', 'Nama', 'SKU', 'Deskripsi', 'Qty', 'Satuan', 'Harga', 'Min Stok', 'Status'], null, 'A1');
        $sheet->fromArray($rows, null, 'A2', true);

        $path = storage_path('app/livewire-import-'.uniqid().'.xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return NamedUploadedFile::make($path);
    }

    public function test_render_shows_summary_and_filtered_list(): void
    {
        Stock::factory()->for($this->user)->available()->create(['name' => 'Kertas']);
        Stock::factory()->for($this->user)->lowStock()->create(['name' => 'Tinta']);
        Stock::factory()->for($this->user)->outOfStock()->create(['name' => 'Map']);

        Livewire::test(StockList::class)
            ->assertSee('Kertas')
            ->assertSee('Tinta')
            ->assertSee('Map')
            ->assertViewHas('counts', fn ($counts) => $counts['total'] === 3 && $counts['low'] === 1 && $counts['out'] === 1)
            ->set('statusFilter', 'low_stock')
            ->assertSee('Tinta')
            ->assertDontSee('Kertas')
            ->set('search', 'Tinta')
            ->assertSee('Tinta')
            ->set('search', 'tidak ada')
            ->assertSee('Belum ada stok');
    }

    public function test_create_modal_and_validation(): void
    {
        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->assertSet('showFormModal', true)
            ->call('saveStock')
            ->assertHasErrors(['name'])
            ->assertSet('showFormModal', true)
            ->set('name', 'Kertas HVS')
            ->set('sku', 'KRS-1')
            ->set('description', 'Kertas putih')
            ->set('quantity', 20)
            ->set('unit', 'rim')
            ->set('price', 55000)
            ->set('minStock', 5)
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $this->user->id,
            'name' => 'Kertas HVS',
            'sku' => 'KRS-1',
            'quantity' => 20,
            'unit' => 'rim',
            'status' => StockStatus::AVAILABLE->value,
        ]);
    }

    public function test_create_stock_with_empty_optional_fields(): void
    {
        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->set('name', 'Map Plastik')
            ->set('sku', '')
            ->set('description', '')
            ->set('price', '')
            ->call('saveStock')
            ->assertHasNoErrors();

        $stock = Stock::firstOrFail();
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertNull($stock->price);
    }

    public function test_edit_modal_updates_stock(): void
    {
        $stock = Stock::factory()->for($this->user)->available()->create([
            'name' => 'Lama',
            'sku' => 'OLD',
            'price' => 1000,
        ]);

        Livewire::test(StockList::class)
            ->call('openEditModal', $stock->id)
            ->assertSet('editingId', $stock->id)
            ->assertSet('name', 'Lama')
            ->assertSet('price', '1000.00')
            ->set('name', 'Baru')
            ->set('quantity', 2)
            ->call('saveStock')
            ->assertHasNoErrors();

        $this->assertSame('Baru', $stock->refresh()->name);
        $this->assertSame(StockStatus::LOW_STOCK, $stock->status);
    }

    public function test_close_form_modal_resets_state(): void
    {
        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->set('name', 'Sementara')
            ->call('closeFormModal')
            ->assertSet('showFormModal', false)
            ->assertSet('name', '')
            ->assertSet('quantity', 0)
            ->assertSet('minStock', 5);
    }

    public function test_adjust_quantity_updates_stock_and_reports_errors(): void
    {
        $stock = Stock::factory()->for($this->user)->available()->create(['quantity' => 10, 'min_stock' => 5]);

        Livewire::test(StockList::class)
            ->call('adjustQuantity', $stock->id, 2)
            ->assertHasNoErrors();

        $this->assertSame(12, $stock->refresh()->quantity);

        Livewire::test(StockList::class)
            ->call('adjustQuantity', $stock->id, -99)
            ->assertHasErrors('adjust');

        $this->assertSame(12, $stock->refresh()->quantity);
    }

    public function test_delete_confirmation_and_deletion(): void
    {
        $stock = Stock::factory()->for($this->user)->create();

        $component = Livewire::test(StockList::class)
            ->call('deleteStock')
            ->assertSet('confirmingDeletionId', null);

        $component->call('confirmDelete', $stock->id)
            ->assertSet('confirmingDeletionId', $stock->id)
            ->call('cancelDelete')
            ->assertSet('confirmingDeletionId', null)
            ->call('confirmDelete', $stock->id)
            ->call('deleteStock');

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_import_requires_a_file(): void
    {
        Livewire::test(StockList::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true)
            ->call('importExcel')
            ->assertHasErrors(['importFile'])
            ->call('closeImportModal')
            ->assertSet('showImportModal', false);
    }

    public function test_import_success_and_error_paths(): void
    {
        $valid = Livewire::test(StockList::class)
            ->call('openImportModal')
            ->set('importFile', $this->importFile([
                [1, 'Kertas Impor', 'IMP-1', null, 9, 'pcs', 1000, 5, null],
            ]))
            ->call('importExcel')
            ->assertHasNoErrors()
            ->assertSet('showImportModal', false);

        $valid->assertSet('importErrors', []);
        $this->assertDatabaseHas('stocks', ['name' => 'Kertas Impor']);

        Livewire::test(StockList::class)
            ->call('openImportModal')
            ->set('importFile', $this->importFile([
                [1, 'Rusak', null, null, -1, 'pcs', null, 5, null],
            ]))
            ->call('importExcel')
            ->assertSet('showImportModal', true)
            ->assertSet('importErrors', ['Baris 2: qty harus bilangan bulat minimal 0, baris dilewati.']);
    }

    public function test_ai_generation_validation_failure_and_success(): void
    {
        $this->mock(AiClientInterface::class, function ($mock) {
            $mock->shouldReceive('generateStocks')
                ->once()
                ->with('Buat stok alat tulis', 2)
                ->andReturn([
                    new GeneratedStockItem(name: 'Pulpen', quantity: 10, unit: 'pcs', price: 3000, minStock: 5),
                    new GeneratedStockItem(name: 'Buku', quantity: 4, unit: 'pcs', minStock: 2),
                ]);
        });

        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->assertSet('showAiModal', true)
            ->call('generateWithAi')
            ->assertHasErrors(['aiPrompt'])
            ->set('aiPrompt', 'Buat stok alat tulis')
            ->set('aiCount', 2)
            ->call('generateWithAi')
            ->assertHasNoErrors()
            ->assertSet('aiLoading', false)
            ->assertSet('aiSelected', [0, 1])
            ->assertSee('Pulpen');
    }

    public function test_ai_generation_handles_runtime_exception(): void
    {
        $this->mock(AiClientInterface::class, function ($mock) {
            $mock->shouldReceive('generateStocks')->once()->andThrow(new RuntimeException('OpenAI gagal'));
        });

        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->set('aiPrompt', 'Buat stok alat tulis')
            ->call('generateWithAi')
            ->assertSet('aiError', 'OpenAI gagal')
            ->assertSet('aiItems', [])
            ->assertSet('aiLoading', false);
    }

    public function test_save_ai_generated_requires_selection_and_persists_chosen_items(): void
    {
        $items = [
            ['name' => 'Pulpen', 'sku' => null, 'description' => null, 'quantity' => 10, 'unit' => 'pcs', 'price' => 3000, 'min_stock' => 5],
            ['name' => 'Buku', 'sku' => 'BK', 'description' => null, 'quantity' => 4, 'unit' => 'pcs', 'price' => null, 'min_stock' => 2],
        ];

        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->set('aiItems', $items)
            ->set('aiSelected', [])
            ->call('saveAiGenerated')
            ->assertHasErrors('aiSelected')
            ->set('aiSelected', ['1'])
            ->call('saveAiGenerated')
            ->assertSet('showAiModal', false);

        $this->assertDatabaseCount('stocks', 1);
        $this->assertDatabaseHas('stocks', ['name' => 'Buku', 'sku' => 'BK']);
    }

    public function test_close_ai_modal_clears_state(): void
    {
        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->set('aiPrompt', 'Sesuatu')
            ->set('aiError', 'gagal')->call('closeAiModal')
            ->assertSet('showAiModal', false)
            ->assertSet('aiItems', [])
            ->assertSet('aiError', '');
    }

    public function test_export_excel_returns_download_response(): void
    {
        Stock::factory()->for($this->user)->count(2)->create();

        $component = Livewire::test(StockList::class);
        $response = $component->instance()->exportExcel(
            app(StockExcelService::class),
            app(StockRepositoryInterface::class),
        );

        $this->assertStringContainsString('stok-', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }
}


class NamedUploadedFile extends UploadedFile
{
    public string $name = 'stok.xlsx';

    public static function make(string $path): self
    {
        return new self($path, 'stok.xlsx', null, null, true);
    }
}

