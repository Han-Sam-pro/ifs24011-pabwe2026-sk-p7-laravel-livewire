<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        $samples = [
            [
                'name' => 'Kertas HVS A4 80gsm',
                'sku' => 'KRT-A4-80',
                'description' => "Kertas putih **A4 80gsm** untuk kebutuhan kantor.\n\n- Isi 500 lembar per rim\n- Merek: PaperOne",
                'quantity' => 50,
                'unit' => 'rim',
                'price' => 55000,
                'min_stock' => 10,
            ],
            [
                'name' => 'Tinta Printer Hitam',
                'sku' => 'TNT-HTM-01',
                'description' => 'Tinta refill printer inkjet warna hitam, volume 100 ml.',
                'quantity' => 3,
                'unit' => 'botol',
                'price' => 32000,
                'min_stock' => 5,
            ],
            [
                'name' => 'Map Plastik Folio',
                'sku' => 'MAP-FL-02',
                'description' => 'Map plastik transparan ukuran folio untuk arsip dokumen.',
                'quantity' => 0,
                'unit' => 'pcs',
                'price' => 2500,
                'min_stock' => 10,
            ],
            [
                'name' => 'Pulpen Gel Hitam',
                'sku' => 'PLP-GEL-03',
                'description' => 'Pulpen gel tinta hitam 0,5 mm, nyaman untuk menulis sehari-hari.',
                'quantity' => 120,
                'unit' => 'pcs',
                'price' => 3500,
                'min_stock' => 24,
            ],
        ];

        foreach ($samples as $sample) {
            Stock::firstOrCreate(
                ['user_id' => $member->id, 'name' => $sample['name']],
                $sample + [
                    'status' => StockStatus::calculate($sample['quantity'], $sample['min_stock'])->value,
                ]
            );
        }
    }
}
