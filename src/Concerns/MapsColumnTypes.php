<?php

namespace Plank\BlueprintData\Concerns;

use Carbon\CarbonImmutable;

trait MapsColumnTypes
{
    /**
     * Map a Blueprint column data type to a PHP type.
     *
     * Returns a fully-qualified class name (leading backslash) for date types,
     * or a scalar type keyword otherwise.
     */
    protected function phpType(string $dataType): string
    {
        return match (strtolower($dataType)) {
            'id', 'integer', 'biginteger', 'unsignedbiginteger', 'tinyinteger',
            'smallinteger', 'mediuminteger', 'unsignedinteger', 'foreignid',
            'increments', 'bigincrements', 'year' => 'int',

            'boolean' => 'bool',

            'decimal', 'unsigneddecimal', 'double', 'float' => 'float',

            'date', 'datetime', 'datetimetz', 'timestamp', 'timestamptz',
            'time', 'timetz' => class_basename($this->dateClass()),

            'json', 'jsonb' => 'array',

            'string', 'char', 'text', 'tinytext', 'mediumtext', 'longtext',
            'uuid', 'ulid', 'ipaddress', 'macaddress', 'enum', 'set',
            'binary', 'geometry', 'point', 'linestring', 'polygon' => 'string',

            default => 'mixed',
        };
    }

    protected function dateClass(): string
    {
        return (string) config('blueprint_data.date_class', CarbonImmutable::class);
    }
}
