<?php

namespace Plank\BlueprintData\Concerns;

use Blueprint\Models\Model;
use Blueprint\Tree;
use Illuminate\Support\Str;
use Plank\BlueprintData\Support\Property;

trait ResolvesRelationships
{
    /**
     * Build Data properties for a model's relationships.
     *
     * To-one relationships become nullable `{Related}Data` properties defaulting
     * to null. To-many relationships become `Collection` properties annotated
     * with `#[DataCollectionOf({Related}Data::class)]`.
     *
     * @return list<Property>
     */
    protected function relationshipProperties(Model $model, Tree $tree): array
    {
        $properties = [];

        $toOne = ['belongsTo', 'hasOne', 'morphOne'];
        $toMany = ['hasMany', 'belongsToMany', 'morphMany', 'morphToMany', 'morphedByMany'];

        foreach ($model->relationships() as $type => $references) {
            if (! in_array($type, [...$toOne, ...$toMany], true)) {
                continue;
            }

            foreach ($references as $reference) {
                $relatedClass = $this->relatedModelClassName($reference);
                $dataClass = $relatedClass.'Data';
                $dataFqcn = $this->dataNamespaceFor($model, $tree).'\\'.$dataClass;

                if (in_array($type, $toOne, true)) {
                    $properties[] = new Property(
                        name: Str::camel($this->toOneMethodName($reference)),
                        type: $dataClass,
                        nullable: true,
                        hasDefault: true,
                        default: 'null',
                        imports: [$dataFqcn],
                    );

                    continue;
                }

                $properties[] = new Property(
                    name: Str::camel(Str::plural($this->toManyMethodName($reference))),
                    type: 'Collection',
                    imports: [
                        'Illuminate\\Support\\Collection',
                        'Spatie\\LaravelData\\Attributes\\DataCollectionOf',
                        $dataFqcn,
                    ],
                    attributes: ['#[DataCollectionOf('.$dataClass.'::class)]'],
                );
            }
        }

        return $properties;
    }

    /**
     * Resolve the base (unqualified) related model class name from a Blueprint
     * relationship reference. Handles `column_id`, `Model`, `foreign.key:column`
     * and fully-qualified `\App\Models\Model` reference forms.
     */
    protected function relatedModelClassName(string $reference): string
    {
        $isFqn = Str::startsWith($reference, '\\');
        $class = null;
        $columnName = $reference;

        if (Str::contains($reference, ':')) {
            [$foreignReference, $columnName] = explode(':', $reference);
            $columnName = ltrim($columnName, '&');

            if (Str::contains($foreignReference, '.')) {
                [$class] = explode('.', $foreignReference);
            } else {
                $class = $foreignReference;
            }
        }

        if ($isFqn) {
            return Str::afterLast($class ?? $columnName, '\\');
        }

        $name = $class ?? Str::beforeLast($columnName, '_id');

        return Str::studly(Str::singular($name));
    }

    protected function toOneMethodName(string $reference): string
    {
        $columnName = Str::contains($reference, ':')
            ? Str::after($reference, ':')
            : $reference;

        $columnName = ltrim($columnName, '&');

        if (Str::startsWith($reference, '\\')) {
            return Str::afterLast($columnName, '\\');
        }

        return Str::beforeLast($columnName, '_id') ?: $columnName;
    }

    protected function toManyMethodName(string $reference): string
    {
        return $this->relatedModelClassName($reference);
    }
}
