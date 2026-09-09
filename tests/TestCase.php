<?php

namespace Plank\BlueprintData\Tests;

use Blueprint\BlueprintServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Plank\BlueprintData\BlueprintDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BlueprintServiceProvider::class,
            BlueprintDataServiceProvider::class,
        ];
    }
}
