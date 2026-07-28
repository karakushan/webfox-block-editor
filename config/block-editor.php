<?php

return [
    'blocks_path' => app_path('Blocks'),

    'blocks_namespace' => 'App\\Blocks',

    'blocks_pattern' => '*Block.php',

    /* Models which may be loaded and saved through the Orchid editor API. */
    'allowed_models' => [],

    /*
    |--------------------------------------------------------------------------
    | Block Preview Images Path
    |--------------------------------------------------------------------------
    |
    | Path to block preview images directory relative to public directory.
    |
    */

    'preview_path' => 'images/blocks',

    /*
    |--------------------------------------------------------------------------
    | Default Block Settings
    |--------------------------------------------------------------------------
    |
    | Default settings that will be applied to all blocks if not specified
    | during block registration.
    |
    */

    'default_settings' => [
        'background_color' => '#ffffff',
        'text_color' => '#000000',
    ],
];
