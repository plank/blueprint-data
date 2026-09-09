# Blueprint Data

Generate [spatie/laravel-data](https://spatie.be/docs/laravel-data) objects straight from your [Laravel Blueprint](https://blueprint.laravelshift.com) draft file. Define your models once, run `blueprint:build`, and get a matching `Data` class for every model, with typed properties and nested relationships already wired up.

It hooks into Blueprint's own build step, so the DTOs stay in sync with the models they describe. No second schema to maintain.

## Installation

Require the package with Composer:

```bash
composer require plank/blueprint-data --dev
```

The package registers itself through Laravel's auto-discovery, and it plugs into Blueprint the moment it boots. Blueprint itself is a dependency, so if you don't already have it, Composer will pull it in.

To customize the defaults, publish the config file:

```bash
php artisan vendor:publish --tag=blueprint-data-config
```

## Quick start

Add a couple of models to your `draft.yaml`:

```yaml
models:
  Author:
    name: string
    bio: text nullable
    relationships:
      hasMany: Book

  Book:
    title: string
    price: decimal:8,2
    published_at: datetime nullable
    author_id: id foreign
    relationships:
      belongsTo: Author
```

Run the build:

```bash
php artisan blueprint:build
```

Alongside the usual models and migrations, you'll find a new `app/Data` directory:

```php
<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class BookData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public float $price,
        public int $author_id,
        public ?CarbonImmutable $published_at = null,
        public ?AuthorData $author = null,
    ) {}
}
```

Only want the DTOs? Scope the build to them:

```bash
php artisan blueprint:build --only=dtos
```

Or generate everything except the DTOs:

```bash
php artisan blueprint:build --skip=dtos
```

## Configuration

Every option lives in `config/blueprint_data.php`. The defaults work for a standard Laravel app, so you can skip this section until you need to change something.

| Key | Default | What it does |
| --- | --- | --- |
| `namespace` | `App\Data` | Namespace for generated classes. Sub-namespaces on a model are preserved beneath it. |
| `output_path` | `app/Data` | Where the files are written. Keep this in step with `namespace`. |
| `include_timestamps` | `true` | Include `created_at` and `updated_at` as properties. |
| `include_soft_deletes` | `false` | Include `deleted_at` for soft-deleting models. |
| `include_relationships` | `true` | Map relationships to nested `Data` properties. |
| `date_class` | `Carbon\CarbonImmutable` | The class used to type date, datetime, and timestamp columns. |

### Namespace and output path

`namespace` and `output_path` describe the same location two ways, one for PHP and one for the filesystem. Change them together. If a model sits in a sub-namespace, that structure carries over: a `Blog\Post` model becomes `App\Data\Blog\PostData` at `app/Data/Blog/PostData.php`.

### Relationships

With `include_relationships` on (the default), each relationship on a model turns into a property:

- A to-one relationship (`belongsTo`, `hasOne`, `morphOne`) becomes a nullable `Data` property, for example `public ?AuthorData $author = null`.
- A to-many relationship (`hasMany`, `belongsToMany`, `morphMany`, and friends) becomes a `Collection` tagged with `#[DataCollectionOf(...)]` so laravel-data knows what it holds.

Set it to `false` if you'd rather generate flat DTOs and wire the relationships up yourself.

## Usage

The generator reads the same model definitions Blueprint already parsed, so anything you can express in a draft flows through to the DTO.

Column types map to native PHP types. Integers become `int`, decimals and floats become `float`, booleans become `bool`, and JSON becomes `array`. Strings, text, UUIDs, and similar text columns become `string`. Date, datetime, and timestamp columns use whatever you set as `date_class`. If the generator doesn't recognize a type, it falls back to `mixed`.

Nullable columns become nullable properties with a `null` default. PHP requires optional constructor parameters to come last, so the generator puts required properties first and defaulted ones after. The output is always valid, whatever order you wrote the columns in.

Rerun `blueprint:build` whenever the draft changes and the DTOs are rewritten to match. Treat them as generated output rather than files you edit by hand. If you need custom logic, extend the generated class.

### How it works

The package is a Blueprint generator, not a separate command. Blueprint is bound as a singleton in the container, and the service provider extends that binding to register a `DataGenerator`:

```php
$this->app->extend(Blueprint::class, function (Blueprint $blueprint, $app) {
    $blueprint->registerGenerator($app[DataGenerator::class]);

    return $blueprint;
});
```

Because of that, there's no lexer to write. Blueprint parses the draft into its own `Model` objects, and the generator walks the resulting tree. Each model gives it the columns and relationships it needs, and it renders them into a `Data` class using a small stub.

The work splits across a few focused pieces:

- `DataGenerator` builds a class per model and writes it out. Its `types()` method returns `['dtos']`, which is what `--only` and `--skip` match against.
- `MapsColumnTypes` translates a Blueprint column type into a PHP type.
- `ResolvesRelationships` reads a model's relationships and turns each one into a nested property. It reuses Blueprint's own reference syntax, so foreign keys, `Model` names, and fully-qualified class references all resolve the way you'd expect.
- `Property` is a small value object that holds one constructor property, its type, imports, and any attributes, and knows how to render itself.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

&nbsp;

## Credits

- [Massimo Triassi](https://github.com/m-triassi)
- [All Contributors](../../contributors)

&nbsp;

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

&nbsp;

## Security Vulnerabilities

If you discover a security vulnerability within siren, please send an e-mail to [security@plank.co](mailto:security@plank.co). All security vulnerabilities will be promptly addressed.

&nbsp;

## Check Us Out!

<a href="https://plank.co/open-source/learn-more-image">
    <img src="https://plank.co/open-source/banner">
</a>

&nbsp;

Plank focuses on impactful solutions that deliver engaging experiences to our clients and their users. We're committed to innovation, inclusivity, and sustainability in the digital space. [Learn more](https://plank.co/open-source/learn-more-link) about our mission to improve the web.