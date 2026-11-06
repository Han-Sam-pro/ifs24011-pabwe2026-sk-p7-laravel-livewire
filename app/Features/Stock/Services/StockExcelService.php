<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockExcelService
{
    /**
     * Export headers, exactly as required by the workflow.
     */
    public const HEADERS = [
        'ID',
        'Nama',
        'SKU',
        'Deskripsi',
        'Qty',
        'Satuan',
        'Harga',
        'Min Stok',
        'Status',
    ];

    public function __construct(
        private readonly StockRepositoryInterface $repository,
        private readonly StockService $stockService,
    ) {}

    /**
     * Build a spreadsheet for the given stocks.
     *
     * @param  Collection<int, Stock>  $stocks
     */
    public function buildSpreadsheet(Collection $stocks, string $sheetTitle = 'Stok'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;

        foreach ($stocks as $stock) {
            $sheet->fromArray([
                $stock->id,
                $stock->name,
                $stock->sku,
                $stock->description,
                $stock->quantity,
                $stock->unit,
                $stock->price !== null ? (float) $stock->price : null,
                $stock->min_stock,
                $stock->status->label(),
            ], null, 'A'.$row);

            $row++;
        }

        $lastRow = max(1, $row - 1);

        $sheet->getStyle('A1:I1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A1:I'.$lastRow)->getBorders()
            ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('G2:G'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Stream the given stocks as an .xlsx download.
     *
     * @param  Collection<int, Stock>  $stocks
     */
    public function exportToResponse(Collection $stocks, string $filename = 'stok.xlsx'): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($stocks);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import stocks from an xlsx, xls or csv file.
     *
     * The status column in the file is ignored; status is always recomputed
     * from quantity and min_stock by StockService.
     *
     * @return array{imported: int, errors: array<int, string>}
     */
    public function import(string $filePath, int $userId): array
    {
        $reader = IOFactory::createReaderForFile($filePath);

        if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');
        }

        $spreadsheet = $reader->load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();

        $imported = 0;
        $errors = [];
        $now = now()->toDateTimeString();
        $records = [];

        foreach ($rows as $index => $row) {
            if ($index === 1) {
                // Header row.
                continue;
            }

            $name = trim((string) ($row['B'] ?? ''));

            if ($name === '') {
                continue;
            }

            $quantityRaw = $row['E'] ?? null;

            if (! is_numeric($quantityRaw) || (float) $quantityRaw != (int) $quantityRaw || (int) $quantityRaw < 0) {
                $errors[] = "Baris {$index}: qty harus bilangan bulat minimal 0, baris dilewati.";

                continue;
            }

            $quantity = (int) $quantityRaw;

            $minStockRaw = $row['H'] ?? null;
            $minStock = is_numeric($minStockRaw) && (int) $minStockRaw >= 0
                ? (int) $minStockRaw
                : 5;

            $unit = trim((string) ($row['F'] ?? ''));
            $unit = $unit === '' ? 'pcs' : $unit;

            $priceRaw = $row['G'] ?? null;
            $price = is_numeric($priceRaw) && (float) $priceRaw >= 0
                ? round((float) $priceRaw, 2)
                : null;

            $sku = trim((string) ($row['C'] ?? ''));
            $description = trim((string) ($row['D'] ?? ''));

            $records[] = [
                'user_id' => $userId,
                'name' => $name,
                'sku' => $sku === '' ? null : $sku,
                'description' => $description === '' ? null : $description,
                'quantity' => $quantity,
                'unit' => $unit,
                'price' => $price,
                'min_stock' => $minStock,
                'status' => $this->stockService->computeStatus($quantity, $minStock)->value,
                'cover_image_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $imported++;
        }

        $this->repository->insertMany($records);

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }
}
