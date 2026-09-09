<?php

namespace Plank\BlueprintData\Support;

/**
 * A single constructor property for a generated Data class.
 */
class Property
{
    /**
     * @param  list<string>  $imports  Fully-qualified class names to import.
     * @param  list<string>  $attributes  Attribute lines (without leading indentation) e.g. "#[DataCollectionOf(TagData::class)]".
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
        public bool $hasDefault = false,
        public ?string $default = null,
        public array $imports = [],
        public array $attributes = [],
    ) {}

    public function render(): string
    {
        $indent = str_repeat(' ', 8);
        $lines = [];

        foreach ($this->attributes as $attribute) {
            $lines[] = $indent.$attribute;
        }

        $type = $this->nullable ? '?'.$this->type : $this->type;
        $signature = $indent.'public '.$type.' $'.$this->name;

        if ($this->hasDefault) {
            $signature .= ' = '.$this->default;
        }

        $lines[] = $signature.',';

        return implode(PHP_EOL, $lines);
    }
}
