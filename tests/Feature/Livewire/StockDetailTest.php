<?php

namespace Tests\Feature\Livewire;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->stock = Stock::factory()->for($this->user)->available()->create([
            'name' => 'Kertas HVS',
            'sku' => 'KRS-1',
            'description' => '**Kertas** putih',
            'quantity' => 20,
            'min_stock' => 5,
            'price' => 55000,
            'unit' => 'rim',
        ]);
        $this->actingAs($this->user);
    }

    public function test_render_shows_stock_information(): void
    {
        Livewire::test(StockDetail::class, ['stock' => $this->stock])
            ->assertSee('Kertas HVS')
            ->assertSee('KRS-1')
            ->assertSee('Rp 55.000')
            ->assertSee('Tersedia')
            ->assertSee('Kertas');
    }

    public function test_edit_modal_saves_changes_and_validates(): void
    {
        Livewire::test(StockDetail::class, ['stock' => $this->stock])
            ->call('openEditModal')
            ->assertSet('showEditModal', true)
            ->assertSet('name', 'Kertas HVS')
            ->set('name', '')
            ->call('saveStock')
            ->assertHasErrors(['name'])
            ->set('name', 'Kertas Baru')
            ->set('quantity', 3)
            ->set('minStock', 5)
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->stock->refresh();
        $this->assertSame('Kertas Baru', $this->stock->name);
        $this->assertSame(StockStatus::LOW_STOCK, $this->stock->status);
    }

    public function test_close_edit_modal_restores_values(): void
    {
        Livewire::test(StockDetail::class, ['stock' => $this->stock])
            ->call('openEditModal')
            ->set('name', 'Sementara')
            ->call('closeEditModal')
            ->assertSet('showEditModal', false)
            ->assertSet('name', 'Kertas HVS');
    }

    public function test_adjust_quantity_and_negative_error(): void
    {
        Livewire::test(StockDetail::class, ['stock' => $this->stock])
            ->call('adjustQuantity', 1)
            ->assertHasNoErrors();

        $this->assertSame(21, $this->stock->refresh()->quantity);

        Livewire::test(StockDetail::class, ['stock' => $this->stock])
            ->call('adjustQuantity', -100)
            ->assertHasErrors('adjust');

        $this->assertSame(21, $this->stock->refresh()->quantity);
    }

    public function test_cover_photo_upload_and_validation(): void
    {
        $component = Livewire::test(StockDetail::class, ['stock' => $this->stock]);
        $instance = $component->instance();

        $path = $this->pngPath();
        $instance->coverPhoto = DetailUploadedFile::make($path, 'cover.png');
        $instance->updatedCoverPhoto(app(StockService::class));

        $this->assertNotNull($this->stock->refresh()->cover_image_path);
        Storage::disk('public')->assertExists($this->stock->cover_image_path);

        $instance->coverPhoto = DetailUploadedFile::make($this->textPath(), 'cover.txt');

        try {
            $instance->updatedCoverPhoto(app(StockService::class));
            $this->fail('Validation should have failed for a non-image file.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('coverPhoto', $exception->errors());
        }
    }

    public function test_cover_deletion_flow(): void
    {
        Storage::disk('public')->put('stock-covers/old.png', 'x');
        $this->stock->update(['cover_image_path' => 'stock-covers/old.png']);

        Livewire::test(StockDetail::class, ['stock' => $this->stock])
            ->call('confirmCoverDeletion')
            ->assertSet('confirmingCoverDeletion', true)
            ->call('cancelCoverDeletion')
            ->assertSet('confirmingCoverDeletion', false)
            ->call('confirmCoverDeletion')
            ->call('deleteCoverPhoto')
            ->assertSet('confirmingCoverDeletion', false);

        $this->assertNull($this->stock->refresh()->cover_image_path);
        Storage::disk('public')->assertMissing('stock-covers/old.png');
    }

    public function test_stock_deletion_flow(): void
    {
        Livewire::test(StockDetail::class, ['stock' => $this->stock])
            ->call('confirmDeletion')
            ->assertSet('confirmingDeletion', true)
            ->call('cancelDeletion')
            ->assertSet('confirmingDeletion', false)
            ->call('confirmDeletion')
            ->call('deleteStock')
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseMissing('stocks', ['id' => $this->stock->id]);
    }

    private function pngPath(): string
    {
        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 120, 200));
        $path = storage_path('app/test-'.uniqid().'.png');
        imagepng($image, $path);

        return $path;
    }

    private function textPath(): string
    {
        $path = storage_path('app/test-'.uniqid().'.txt');
        file_put_contents($path, 'not an image');

        return $path;
    }
}

class DetailUploadedFile extends UploadedFile
{
    public string $name;

    public static function make(string $path, string $name): self
    {
        $file = new self($path, $name, null, null, true);
        $file->name = $name;

        return $file;
    }
}
