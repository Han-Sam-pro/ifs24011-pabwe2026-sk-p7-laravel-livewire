<?php

namespace Tests\Feature\Ai;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('openai.endpoint', 'https://api.openai.com/v1');
        config()->set('openai.key', 'test-key');
        config()->set('openai.model', 'gpt-test');
    }

    private function fakeContent(string $content): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => $content]],
                ],
            ]),
        ]);
    }

    public function test_it_uses_configured_defaults_and_parses_fenced_stocks(): void
    {
        $this->fakeContent("```json\n".json_encode([
            'stocks' => [
                [
                    'name' => 'Kertas HVS',
                    'sku' => ' KRS-1 ',
                    'description' => ' Kertas putih ',
                    'quantity' => '12',
                    'unit' => 'rim',
                    'price' => '55000.555',
                    'min_stock' => '4',
                ],
            ],
        ])."\n```");

        $items = (new OpenAiStockGeneratorService)->generateStocks('Buat stok alat tulis', 3);

        $this->assertCount(1, $items);
        $this->assertInstanceOf(GeneratedStockItem::class, $items[0]);
        $this->assertSame('Kertas HVS', $items[0]->name);
        $this->assertSame('KRS-1', $items[0]->sku);
        $this->assertSame('Kertas putih', $items[0]->description);
        $this->assertSame(12, $items[0]->quantity);
        $this->assertSame('rim', $items[0]->unit);
        $this->assertSame(55000.56, $items[0]->price);
        $this->assertSame(4, $items[0]->minStock);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'gpt-test'
                && $request['temperature'] === 0.7;
        });
    }

    public function test_it_swaps_endpoint_and_key_when_reversed(): void
    {
        $this->fakeContent('{"stocks":[{"name":"Map"}]}');

        (new OpenAiStockGeneratorService('sk-secret', 'https://example.test/v1/'))->generateStocks('Buat stok');

        Http::assertSent(fn ($request) => $request->url() === 'https://example.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer sk-secret'));
    }

    public function test_it_adds_https_scheme_and_trims_trailing_slash(): void
    {
        $this->fakeContent('{"stocks":[{"name":"Map"}]}');

        (new OpenAiStockGeneratorService('example.test/v1/', 'my-key', 'custom-model'))->generateStocks('Buat stok');

        Http::assertSent(fn ($request) => $request->url() === 'https://example.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer my-key')
            && $request['model'] === 'custom-model');
    }

    public function test_it_rejects_an_empty_prompt(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');

        (new OpenAiStockGeneratorService)->generateStocks('   ');
    }

    public function test_it_clamps_count_between_one_and_ten(): void
    {
        $this->fakeContent('{"stocks":[{"name":"Map"}]}');

        (new OpenAiStockGeneratorService)->generateStocks('Buat stok', 0);
        (new OpenAiStockGeneratorService)->generateStocks('Buat stok', 99);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], '1 item'));
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], '10 item'));
    }

    public function test_it_accepts_items_key_and_plain_list(): void
    {
        $this->fakeContent('{"items":[{"name":"Pulpen","quantity":-3,"min_stock":-1}]}');
        $items = (new OpenAiStockGeneratorService)->generateStocks('Buat stok');
        $this->assertSame('Pulpen', $items[0]->name);
        $this->assertSame(0, $items[0]->quantity);
        $this->assertSame(0, $items[0]->minStock);
        $this->assertSame('pcs', $items[0]->unit);
        $this->assertNull($items[0]->price);
    }

    public function test_it_accepts_a_plain_json_list_and_skips_invalid_entries(): void
    {
        $this->fakeContent('[{"name":"Buku","price":-5,"unit":""},{"foo":"bar"},{"name":"Penghapus"}]');

        $items = (new OpenAiStockGeneratorService)->generateStocks('Buat stok');

        $this->assertCount(2, $items);
        $this->assertSame('Buku', $items[0]->name);
        $this->assertNull($items[0]->price);
        $this->assertSame('pcs', $items[0]->unit);
        $this->assertNull($items[0]->sku);
        $this->assertNull($items[0]->description);
    }

    public function test_it_skips_non_array_items_and_defaults_non_numeric_numbers(): void
    {
        $this->fakeContent('{"stocks":["bukan objek",{"name":"Pulpen","quantity":"abc","min_stock":"xyz"}]}');

        $items = (new OpenAiStockGeneratorService)->generateStocks('Buat stok');

        $this->assertCount(1, $items);
        $this->assertSame('Pulpen', $items[0]->name);
        $this->assertSame(0, $items[0]->quantity);
        $this->assertSame(5, $items[0]->minStock);
    }

    public function test_it_rejects_invalid_json(): void
    {
        $this->fakeContent('bukan json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: JSON tidak valid.');

        (new OpenAiStockGeneratorService)->generateStocks('Buat stok');
    }

    public function test_it_rejects_json_without_a_stock_list(): void
    {
        $this->fakeContent('{"stocks":"bukan daftar"}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: daftar stok tidak ditemukan.');

        (new OpenAiStockGeneratorService)->generateStocks('Buat stok');
    }

    public function test_it_rejects_json_without_named_items(): void
    {
        $this->fakeContent('{"stocks":[{"foo":"bar"}]}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: tidak ada item stok yang valid.');

        (new OpenAiStockGeneratorService)->generateStocks('Buat stok');
    }

    public function test_it_reports_failed_http_responses(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: 500 server error');

        (new OpenAiStockGeneratorService)->generateStocks('Buat stok');
    }

    public function test_it_rejects_responses_without_content(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '  ']]]])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: respons tidak berisi konten.');

        (new OpenAiStockGeneratorService)->generateStocks('Buat stok');
    }
}
