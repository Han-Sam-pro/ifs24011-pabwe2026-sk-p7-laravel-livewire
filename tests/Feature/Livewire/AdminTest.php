<?php

namespace Tests\Feature\Livewire;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_user_list_renders_and_searches(): void
    {
        $member = User::factory()->member()->create(['name' => 'Anggota Satu']);
        Stock::factory()->for($member)->lowStock()->create();
        Stock::factory()->for($member)->outOfStock()->create();

        Livewire::test(UserList::class)
            ->assertSee('Anggota Satu')
            ->assertSee('1')
            ->set('search', 'Anggota')
            ->assertSee('Anggota Satu')
            ->set('search', 'tidak ada')
            ->assertSee('Tidak ada pengguna');
    }

    public function test_admin_user_stock_list_filters_and_is_read_only(): void
    {
        $member = User::factory()->member()->create(['name' => 'Anggota Stok']);
        Stock::factory()->for($member)->available()->create(['name' => 'Kertas Admin']);
        Stock::factory()->for($member)->lowStock()->create(['name' => 'Tinta Admin']);

        Livewire::test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Mode Admin')
            ->assertSee('Kertas Admin')
            ->assertSee('Tinta Admin')
            ->set('statusFilter', 'low_stock')
            ->assertSee('Tinta Admin')
            ->assertDontSee('Kertas Admin')
            ->set('statusFilter', '')
            ->set('search', 'Kertas')
            ->assertSee('Kertas Admin')
            ->assertDontSee('Tinta Admin');

        $this->assertSame(2, Stock::where('user_id', $member->id)->count());
    }

    public function test_admin_user_stock_export(): void
    {
        $member = User::factory()->member()->create(['username' => 'anggota']);
        Stock::factory()->for($member)->create();

        $component = Livewire::test(AdminUserStockList::class, ['user' => $member]);
        $response = $component->instance()->exportExcel(
            app(StockExcelService::class),
            app(StockRepositoryInterface::class),
        );

        $this->assertStringContainsString('stok-anggota-', $response->headers->get('content-disposition'));
    }
}
