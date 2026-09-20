<?php

namespace App\Providers;

use App\Interfaces\DeviceRepositoryInterface;
use App\Interfaces\IngestRepositoryInterface;
use App\Interfaces\ReadingRepositoryInterface;
use App\Interfaces\SensorRepositoryInterface;
use App\Repositories\DeviceRepository;
use App\Repositories\IngestRepository;
use App\Repositories\ReadingRepository;
use App\Repositories\SensorRepository;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\RouteInfo;
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
        $this->app->bind(IngestRepositoryInterface::class, IngestRepository::class);
        $this->app->bind(SensorRepositoryInterface::class, SensorRepository::class);
        $this->app->bind(ReadingRepositoryInterface::class, ReadingRepository::class);
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

        Scramble::configure()
        ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo) {
            $middlewares = $routeInfo->route->gatherMiddleware();

            if (collect($middlewares)->contains('device.auth')) {
                $operation->addParameters([
                    Parameter::make('X-Api-Key', 'header')
                        ->description('Wajib diisi untuk verifikasi device')
                        ->required(true)
                ]);
            }
        });
    }
}
