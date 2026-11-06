<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockExcelService $excel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->excel = app(StockExcelService::class);
    }

    private function writeWorkbook(array $rows, string $name = 'import.xlsx'): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'ID', 'Nama', 'SKU', 'Deskripsi', 'Qty', 'Satuan', 'Harga', 'Min Stok', 'Status',
        ], null, 'A1');

        $sheet->fromArray($rows, null, 'A2', true);

        $path = storage_path('app/'.$name);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    public function test_build_spreadsheet_writes_headers_and_rows(): void
    {
        $user = User::factory()->create();
        $stocks = collect([
            Stock::factory()->for($user)->available()->create([
                'name' => 'Kertas', 'sku' => 'KRS', 'description' => 'HVS', 'quantity' => 10,
                'unit' => 'rim', 'price' => 55000.5, 'min_stock' => 2,
            ]),
            Stock::factory()->for($user)->outOfStock()->create([
                'name' => 'Map', 'sku' => null, 'description' => null, 'quantity' => 0,
                'unit' => 'pcs', 'price' => null, 'min_stock' => 5,
            ]),
        ]);

        $spreadsheet = $this->excel->buildSpreadsheet($stocks);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Stok', $sheet->getTitle());
        $this->assertSame('ID', $sheet->getCell('A1')->getValue());
        $this->assertSame('Nama', $sheet->getCell('B1')->getValue());
        $this->assertSame('Status', $sheet->getCell('I1')->getValue());
        $this->assertSame('Kertas', $sheet->getCell('B2')->getValue());
        $this->assertSame('Tersedia', $sheet->getCell('I2')->getValue());
        $this->assertNull($sheet->getCell('G3')->getValue());
        $this->assertSame('Stok Habis', $sheet->getCell('I3')->getValue());
    }

    public function test_build_spreadsheet_with_custom_title_and_no_rows(): void
    {
        $spreadsheet = $this->excel->buildSpreadsheet(collect(), 'Gudang');
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Gudang', $sheet->getTitle());
        $this->assertSame('ID', $sheet->getCell('A1')->getValue());
        $this->assertNull($sheet->getCell('A2')->getValue());
    }

    public function test_export_to_response_streams_xlsx(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->create(['name' => 'Kertas']);

        $response = $this->excel->exportToResponse(Stock::all(), 'stok-test.xlsx');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('stok-test.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertNotEmpty($content);
    }

    public function test_import_reads_rows_defaults_and_ignores_file_status(): void
    {
        $user = User::factory()->create();

        $path = $this->writeWorkbook([
            [1, 'Kertas HVS', 'KRS-1', 'Deskripsi kertas', 50, 'rim', 55000, 10, 'out_of_stock'],
            [2, 'Tinta', null, null, 3, null, null, null, 'available'],
            [3, '', null, null, 9, null, null, null, null],
            [4, 'Map', 'MP-1', '', 0, 'pcs', 2500.5, 0, null],
        ]);

        $result = $this->excel->import($path, $user->id);

        $this->assertSame(3, $result['imported']);
        $this->assertSame([], $result['errors']);

        $kertas = Stock::where('name', 'Kertas HVS')->firstOrFail();
        $this->assertSame('KRS-1', $kertas->sku);
        $this->assertSame('Deskripsi kertas', $kertas->description);
        $this->assertSame(50, $kertas->quantity);
        $this->assertSame('rim', $kertas->unit);
        $this->assertSame('55000.00', $kertas->price);
        $this->assertSame(10, $kertas->min_stock);
        $this->assertSame(StockStatus::AVAILABLE, $kertas->status);
        $this->assertSame($user->id, $kertas->user_id);

        $tinta = Stock::where('name', 'Tinta')->firstOrFail();
        $this->assertNull($tinta->sku);
        $this->assertNull($tinta->description);
        $this->assertNull($tinta->price);
        $this->assertSame('pcs', $tinta->unit);
        $this->assertSame(5, $tinta->min_stock);
        $this->assertSame(StockStatus::LOW_STOCK, $tinta->status);

        $map = Stock::where('name', 'Map')->firstOrFail();
        $this->assertNull($map->description);
        $this->assertSame(0, $map->min_stock);
        $this->assertSame(StockStatus::OUT_OF_STOCK, $map->status);
    }

    public function test_import_collects_errors_for_invalid_quantity_but_still_imports_valid_rows(): void
    {
        $user = User::factory()->create();

        $path = $this->writeWorkbook([
            [1, 'Valid', null, null, 10, 'pcs', 1000, 5, null],
            [2, 'Negatif', null, null, -2, 'pcs', 1000, 5, null],
            [3, 'Pecahan', null, null, 1.5, 'pcs', 1000, 5, null],
            [4, 'Teks', null, null, 'banyak', 'pcs', 1000, 5, null],
        ]);

        $result = $this->excel->import($path, $user->id);

        $this->assertSame(1, $result['imported']);
        $this->assertCount(3, $result['errors']);
        $this->assertSame('Baris 3: qty harus bilangan bulat minimal 0, baris dilewati.', $result['errors'][0]);
        $this->assertSame('Baris 4: qty harus bilangan bulat minimal 0, baris dilewati.', $result['errors'][1]);
        $this->assertSame('Baris 5: qty harus bilangan bulat minimal 0, baris dilewati.', $result['errors'][2]);
        $this->assertDatabaseCount('stocks', 1);
    }

    public function test_import_supports_csv_files(): void
    {
        $user = User::factory()->create();

        $path = storage_path('app/import.csv');
        file_put_contents($path, "ID,Nama,SKU,Deskripsi,Qty,Satuan,Harga,Min Stok,Status\n1,Strapler,STP,Alat,7,pcs,15000,3,available\n");

        $result = $this->excel->import($path, $user->id);

        $this->assertSame(1, $result['imported']);
        $stock = Stock::firstOrFail();
        $this->assertSame('Strapler', $stock->name);
        $this->assertSame(7, $stock->quantity);
        $this->assertSame(StockStatus::AVAILABLE, $stock->status);
    }

    public function test_import_with_only_header_imports_nothing(): void
    {
        $user = User::factory()->create();
        $path = $this->writeWorkbook([]);

        $result = $this->excel->import($path, $user->id);

        $this->assertSame(0, $result['imported']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseCount('stocks', 0);
    }
}
