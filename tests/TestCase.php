<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tes tidak boleh bergantung pada hasil `npm run build` (public/build/manifest.json).
        // Arahkan Vite ke hot file fixture agar @vite di layout tidak mencari manifest.
        Vite::useHotFile(base_path('tests/fixtures/vite.hot'));
    }
}
