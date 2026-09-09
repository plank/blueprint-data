<?php

namespace Plank\BlueprintData;

use Blueprint\Blueprint;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class BlueprintDataServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/blueprint_data.php' => config_path('blueprint_data.php'),
            ], 'blueprint-data-config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/config/blueprint_data.php',
            'blueprint_data'
        );

        $this->app->singleton(DataGenerator::class, fn ($app) => new DataGenerator($app['files']));

        $this->app->extend(Blueprint::class, function (Blueprint $blueprint, $app) {
            $blueprint->registerGenerator($app[DataGenerator::class]);

            return $blueprint;
        });
    }

    /**
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            DataGenerator::class,
            Blueprint::class,
        ];
    }
}
