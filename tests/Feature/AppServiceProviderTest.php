<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_container_bindings_are_registered(): void
    {
        $this->assertInstanceOf(StockRepository::class, app(StockRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_force_https_config_forces_url_scheme(): void
    {
        config()->set('app.force_https', true);

        $provider = new \App\Providers\AppServiceProvider(app());
        $provider->register();
        $provider->boot();

        $this->assertStringStartsWith('https://', url('/stok'));
    }

    public function test_rate_limiters_use_user_id_or_ip(): void
    {
        $limiter = RateLimiter::limiter('1000-per-second');
        $this->assertNotNull($limiter);

        $guestRequest = Request::create('/stocks');
        $this->assertSame($guestRequest->ip(), $limiter($guestRequest)->key);

        $user = User::factory()->create();
        $authRequest = Request::create('/stocks');
        $authRequest->setUserResolver(fn () => $user);
        $this->assertSame($user->id, $limiter($authRequest)->key);

        foreach (['global', 'api'] as $name) {
            $this->assertNotNull(RateLimiter::limiter($name));
        }
    }
}
