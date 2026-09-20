<?php

namespace App\Providers;

use App\Interfaces\DeviceRepositoryInterface;
use App\Repositories\DeviceRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DeviceRepositoryInterface::class, DeviceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('device', function (Request $request) {
            $device = $request->attributes->get('device');
            return Limit::perMinute(10)->by($device?->id ?? 'ip');
        });
    }
}
