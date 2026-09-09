<?php

use Carbon\CarbonImmutable;

return [
    /*
    |--------------------------------------------------------------------------
    | Data Class Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace under which generated Data classes will be placed. Any
    | sub-namespace a model lives in (relative to the models namespace) is
    | preserved beneath this namespace.
    |
    */
    'namespace' => 'App\\Data',

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | The path, relative to the project root, where generated Data classes are
    | written. This should mirror the configured namespace.
    |
    */
    'output_path' => 'app/Data',

    /*
    |--------------------------------------------------------------------------
    | Timestamps
    |--------------------------------------------------------------------------
    |
    | When enabled, the `created_at` and `updated_at` columns are included as
    | properties on the generated Data class.
    |
    */
    'include_timestamps' => true,

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | When enabled, the `deleted_at` column is included as a property on the
    | generated Data class for models using soft deletes.
    |
    */
    'include_soft_deletes' => false,

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | When enabled, model relationships are mapped to nested Data properties.
    | To-one relationships become nullable `{Related}Data` properties and
    | to-many relationships become `Collection` properties annotated with the
    | `#[DataCollectionOf]` attribute.
    |
    */
    'include_relationships' => true,

    /*
    |--------------------------------------------------------------------------
    | Date Class
    |--------------------------------------------------------------------------
    |
    | The class used to type date, datetime, and timestamp columns.
    |
    */
    'date_class' => CarbonImmutable::class,
];
