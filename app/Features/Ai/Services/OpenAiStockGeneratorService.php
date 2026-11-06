<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI-compatible chat completion client that returns stock items.
 */
class OpenAiStockGeneratorService implements AiClientInterface
{
    private string $endpoint;

    private string $apiKey;

    private string $model;

    private int $timeout;

    public function __construct(?string $endpoint = null, ?string $apiKey = null, ?string $model = null, ?int $timeout = null)
    {
        $endpoint = $endpoint ?? (string) config('openai.endpoint', 'https://api.openai.com/v1');
        $apiKey = $apiKey ?? (string) config('openai.key', '');
        $this->model = $model ?? (string) config('openai.model', 'gpt-4o-mini');
        $this->timeout = $timeout ?? (int) config('openai.timeout', 30);

        // Be forgiving when endpoint and key are swapped in .env.
        if (str_starts_with($endpoint, 'sk-') && $apiKey !== '') {
            [$endpoint, $apiKey] = [$apiKey, $endpoint];
        }

        if (! str_starts_with($endpoint, 'http://') && ! str_starts_with($endpoint, 'https://')) {
            $endpoint = 'https://'.$endpoint;
        }

        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey = $apiKey;
    }

    public function generateStocks(string $prompt, int $count = 3): array
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new RuntimeException('Prompt tidak boleh kosong.');
        }

        $count = max(1, min(10, $count));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)->post($this->endpoint.'/chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah asisten inventaris toko. Buat data stok barang yang masuk akal dalam format JSON. '
                        .'Balas HANYA dengan JSON: {"stocks":[{"name":"...","sku":"...","description":"...","quantity":10,"unit":"pcs","price":15000,"min_stock":5}]}. '
                        .'quantity dan min_stock berupa bilangan bulat >= 0, price berupa angka atau null, unit singkat seperti pcs/box/kg.',
                ],
                [
                    'role' => 'user',
                    'content' => "Buat {$count} item stok untuk: {$prompt}",
                ],
            ],
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI API Error: '.$response->status().' '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI API Error: respons tidak berisi konten.');
        }

        return $this->parseContent($content);
    }

    /**
     * @return array<int, GeneratedStockItem>
     */
    private function parseContent(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;

        $decoded = json_decode(trim($content), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI API Error: JSON tidak valid.');
        }

        $items = $decoded['stocks'] ?? $decoded['items'] ?? $decoded;

        if (! is_array($items)) {
            throw new RuntimeException('OpenAI API Error: daftar stok tidak ditemukan.');
        }

        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $quantity = $this->toNonNegativeInt($item['quantity'] ?? 0, 0);
            $minStock = $this->toNonNegativeInt($item['min_stock'] ?? 5, 5);

            $unit = trim((string) ($item['unit'] ?? ''));
            $unit = $unit === '' ? 'pcs' : $unit;

            $priceRaw = $item['price'] ?? null;
            $price = is_numeric($priceRaw) && (float) $priceRaw >= 0
                ? round((float) $priceRaw, 2)
                : null;

            $sku = trim((string) ($item['sku'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));

            $result[] = new GeneratedStockItem(
                name: $name,
                sku: $sku === '' ? null : $sku,
                description: $description === '' ? null : $description,
                quantity: $quantity,
                unit: $unit,
                price: $price,
                minStock: $minStock,
            );
        }

        if ($result === []) {
            throw new RuntimeException('OpenAI API Error: tidak ada item stok yang valid.');
        }

        return $result;
    }

    private function toNonNegativeInt(mixed $value, int $default): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return max(0, (int) $value);
    }
}
