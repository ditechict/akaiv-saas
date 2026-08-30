<?php

return [
    'media-library' => [
        'disk_name' => env('MEDIA_DISK', 's3'),
        'max_file_size' => 1024 * 1024 * 256,
        'queue_name' => '',
        'queue_connection' => env('QUEUE_CONNECTION', 'redis'),
        'path_generator' => Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator::class,
        'file_namer' => Spatie\MediaLibrary\Support\FileNamer\DefaultFileNamer::class,
        'media_model' => Spatie\MediaLibrary\MediaCollections\Models\Media::class,
        'remote' => [
            'extra_headers' => [
                'CacheControl' => 'max-age=604800',
            ],
        ],
        'responsive_images' => [
            'width_calculator' => Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator::class,
            'use_original_images' => true,
            'force_generate' => false,
        ],
        'use_temporary_directory_for_uploads' => true,
        'generate_responsive_images' => false,
        'image_optimizers' => [
            Spatie\ImageOptimizer\Optimizers\Jpegoptim::class => [
                '-m85',
                '--strip-all',
                '--all-progressive',
            ],
            Spatie\ImageOptimizer\Optimizers\Pngquant::class => [
                '--force',
                '--skip-if-larger',
                '--quality=85',
            ],
            Spatie\ImageOptimizer\Optimizers\Optipng::class => [
                '-i0',
                '-o2',
                '-quiet',
            ],
            Spatie\ImageOptimizer\Optimizers\Svgo::class => [
                '--disable=cleanupIDs',
            ],
            Spatie\ImageOptimizer\Optimizers\Gifsicle::class => [
                '-b',
                '-O3',
            ],
        ],
        'fallback_url' => '',
        'fallback_path' => '',
    ],
];
