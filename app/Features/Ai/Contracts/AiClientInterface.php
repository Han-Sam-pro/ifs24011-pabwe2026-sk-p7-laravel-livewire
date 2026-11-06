<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedStockItem;

interface AiClientInterface
{
    /**
     * Generate stock items from a natural language prompt.
     *
     * @return array<int, GeneratedStockItem>
     *
     * @throws \RuntimeException when the AI request or response is invalid
     */
    public function generateStocks(string $prompt, int $count = 3): array;
}
