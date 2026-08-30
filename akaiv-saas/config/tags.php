<?php

return [
    'models' => [
        'tag' => Spatie\Tags\Tag::class,
    ],
    'tables' => [
        'tags' => 'tags',
        'taggables' => 'taggables',
    ],
    'column_names' => [
        'model_morph_key' => 'model_id',
    ],
    'translatable' => false,
    'enable_inertia_teams' => false,
];
