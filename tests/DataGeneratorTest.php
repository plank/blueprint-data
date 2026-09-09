<?php

use Blueprint\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Plank\BlueprintData\DataGenerator;

function buildTree(string $yamlPath)
{
    /** @var Blueprint $blueprint */
    $blueprint = app(Blueprint::class);

    $tokens = $blueprint->parse(file_get_contents($yamlPath));

    return $blueprint->analyze($tokens);
}

/**
 * Generate Data classes into a temporary output path and return their contents
 * keyed by relative path.
 *
 * @return array<string, string>
 */
function generate(string $yamlPath): array
{
    $base = sys_get_temp_dir().'/blueprint-data-'.uniqid();
    config()->set('blueprint_data.output_path', $base.'/app/Data');

    $tree = buildTree($yamlPath);

    (new DataGenerator(new Filesystem))->output($tree);

    $files = [];
    foreach ((new Filesystem)->allFiles($base) as $file) {
        $relative = 'app/Data/'.ltrim(str_replace($base.'/app/Data', '', $file->getPathname()), '/');
        $files[$relative] = $file->getContents();
    }

    return $files;
}

it('generates a Data class for every model', function () {
    $files = generate(__DIR__.'/fixtures/draft.yaml');

    expect(array_keys($files))
        ->toContain('app/Data/AuthorData.php')
        ->toContain('app/Data/BookData.php')
        ->toContain('app/Data/TagData.php');
});

it('extends the spatie Data class', function () {
    $files = generate(__DIR__.'/fixtures/draft.yaml');

    expect($files['app/Data/AuthorData.php'])
        ->toContain('namespace App\Data;')
        ->toContain('use Spatie\LaravelData\Data;')
        ->toContain('class AuthorData extends Data');
});

it('maps column types to typed properties with nullable defaults last', function () {
    $files = generate(__DIR__.'/fixtures/draft.yaml');
    $book = $files['app/Data/BookData.php'];

    expect($book)
        ->toContain('public string $title,')
        ->toContain('public float $price,')
        ->toContain('public bool $published,')
        ->toContain('public int $author_id,')
        ->toContain('public ?CarbonImmutable $published_at = null,')
        ->toContain('public ?array $metadata = null,');

    expect(strpos($book, 'public string $title,'))
        ->toBeLessThan(strpos($book, '$published_at = null'));
});

it('maps to-one relationships to nullable Data properties', function () {
    $files = generate(__DIR__.'/fixtures/draft.yaml');

    expect($files['app/Data/BookData.php'])
        ->toContain('public ?AuthorData $author = null,');
});

it('maps to-many relationships to annotated collections', function () {
    $files = generate(__DIR__.'/fixtures/draft.yaml');
    $author = $files['app/Data/AuthorData.php'];

    expect($author)
        ->toContain('use Illuminate\Support\Collection;')
        ->toContain('use Spatie\LaravelData\Attributes\DataCollectionOf;')
        ->toContain('#[DataCollectionOf(BookData::class)]')
        ->toContain('public Collection $books,');

    // BookData lives in the same namespace, so no import is emitted for it.
    expect($author)->not->toContain('use App\Data\BookData;');
});
