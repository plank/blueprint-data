<?php

use Illuminate\Filesystem\Filesystem;
use Plank\BlueprintData\DataGenerator;

function typeMapper(): object
{
    return new class(new Filesystem) extends DataGenerator
    {
        public function map(string $dataType): string
        {
            return $this->phpType($dataType);
        }
    };
}

it('maps blueprint data types to php types', function (string $blueprint, string $expected) {
    expect(typeMapper()->map($blueprint))->toBe($expected);
})->with([
    ['integer', 'int'],
    ['id', 'int'],
    ['biginteger', 'int'],
    ['string', 'string'],
    ['text', 'string'],
    ['uuid', 'string'],
    ['boolean', 'bool'],
    ['decimal', 'float'],
    ['float', 'float'],
    ['json', 'array'],
    ['date', 'CarbonImmutable'],
    ['datetime', 'CarbonImmutable'],
    ['timestamp', 'CarbonImmutable'],
    ['unknowntype', 'mixed'],
]);
