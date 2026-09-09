<?php

namespace Plank\BlueprintData\Concerns;

trait HasStubPath
{
    protected function stubPath(): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'stubs';
    }
}
