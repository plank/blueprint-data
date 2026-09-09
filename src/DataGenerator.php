<?php

namespace Plank\BlueprintData;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Plank\BlueprintData\Concerns\HasStubPath;
use Plank\BlueprintData\Concerns\MapsColumnTypes;
use Plank\BlueprintData\Concerns\ResolvesRelationships;
use Plank\BlueprintData\Support\Property;

class DataGenerator implements Generator
{
    use HasStubPath;
    use MapsColumnTypes;
    use ResolvesRelationships;

    /**
     * Columns that are handled specially by configuration toggles.
     *
     * @var list<string>
     */
    protected array $timestampColumns = ['created_at', 'updated_at'];

    public function __construct(private Filesystem $files) {}

    public function output(Tree $tree): array
    {
        $output = [];
        $stub = $this->files->get($this->stubPath().DIRECTORY_SEPARATOR.'data.stub');

        foreach ($tree->models() as $model) {
            $path = $this->getPath($model);

            if (! $this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $created = ! $this->files->exists($path);

            $this->files->put($path, $this->populateStub($stub, $model, $tree));

            $output[$created ? 'created' : 'updated'][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return ['dtos'];
    }

    protected function getPath(Model $model): string
    {
        return rtrim(config('blueprint_data.output_path', 'app/Data'), '/')
            .'/'.$this->modelSubPath($model).$model->name().'Data.php';
    }

    protected function modelSubPath(Model $model): string
    {
        $modelsNamespace = trim((string) config('blueprint.models_namespace'), '\\');
        $sub = Str::of($model->namespace())
            ->when($modelsNamespace !== '', fn ($s) => $s->after($modelsNamespace))
            ->trim('\\')
            ->replace('\\', '/');

        return $sub->isEmpty() ? '' : $sub.'/';
    }

    protected function populateStub(string $stub, Model $model, Tree $tree): string
    {
        $namespace = $this->dataNamespaceForPath($model);

        /** @var list<Property> $properties */
        $properties = [];

        foreach ($this->columnProperties($model) as $property) {
            $properties[] = $property;
        }

        if (config('blueprint_data.include_relationships', true)) {
            foreach ($this->relationshipProperties($model, $tree) as $property) {
                $properties[] = $property;
            }
        }

        $properties = $this->sortProperties($properties);

        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $model->name().'Data', $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($properties, $namespace), $stub);

        return str_replace(
            '{{ properties }}',
            implode(PHP_EOL, array_map(fn (Property $p) => $p->render(), $properties)),
            $stub
        );
    }

    /**
     * @return list<Property>
     */
    protected function columnProperties(Model $model): array
    {
        $properties = [];

        foreach ($model->columns() as $column) {
            $name = $column->name();

            if (in_array($name, $this->timestampColumns, true) && ! config('blueprint_data.include_timestamps', true)) {
                continue;
            }

            if ($name === 'deleted_at' && ! config('blueprint_data.include_soft_deletes', false)) {
                continue;
            }

            $type = $this->phpType($column->dataType());
            $nullable = $column->isNullable();

            $imports = [];
            if ($type === class_basename($this->dateClass())) {
                $imports[] = ltrim($this->dateClass(), '\\');
            }

            $properties[] = new Property(
                name: $name,
                type: $type,
                nullable: $nullable,
                hasDefault: $nullable,
                default: $nullable ? 'null' : null,
                imports: $imports,
            );
        }

        return $properties;
    }

    /**
     * Nullable / defaulted properties must appear after required ones for valid
     * PHP constructor signatures. Preserve original order within each group.
     *
     * @param  list<Property>  $properties
     * @return list<Property>
     */
    protected function sortProperties(array $properties): array
    {
        $required = array_filter($properties, fn (Property $p) => ! $p->hasDefault);
        $optional = array_filter($properties, fn (Property $p) => $p->hasDefault);

        return array_values([...$required, ...$optional]);
    }

    /**
     * @param  list<Property>  $properties
     */
    protected function buildImports(array $properties, string $namespace): string
    {
        $imports = [];

        foreach ($properties as $property) {
            foreach ($property->imports as $import) {
                $import = ltrim($import, '\\');

                // Skip imports that resolve to the current namespace.
                if (Str::beforeLast($import, '\\') === $namespace) {
                    continue;
                }

                $imports[$import] = true;
            }
        }

        if ($imports === []) {
            return '';
        }

        $lines = array_map(fn ($fqcn) => 'use '.$fqcn.';', array_keys($imports));
        sort($lines);

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * Resolve the Data namespace for a model, preserving sub-namespaces.
     */
    protected function dataNamespaceFor(Model $model, ?Tree $tree): string
    {
        return $this->dataNamespaceForPath($model);
    }

    protected function dataNamespaceForPath(Model $model): string
    {
        $base = trim((string) config('blueprint_data.namespace', 'App\\Data'), '\\');
        $modelsNamespace = trim((string) config('blueprint.models_namespace'), '\\');

        $sub = Str::of($model->namespace())
            ->when($modelsNamespace !== '', fn ($s) => $s->after($modelsNamespace))
            ->trim('\\');

        return $sub->isEmpty() ? $base : $base.'\\'.$sub;
    }
}
