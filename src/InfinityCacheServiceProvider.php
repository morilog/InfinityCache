<?php
namespace Morilog\InfinityCache;

use Illuminate\Support\ServiceProvider;

class InfinityCacheServiceProvider extends ServiceProvider
{
    protected $defer  = false;

    public function boot()
    {
        // Publish configs
        $this->publishes([
            __DIR__ . '/../config/infinity-cache.php' => config_path('infinity-cache.php')
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
}