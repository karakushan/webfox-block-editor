<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Console\Commands;

use Illuminate\Console\Command;

class InstallBlockBuilderCommand extends Command
{
    protected $signature = 'block-builder:install {--force : Overwrite published assets and config}';

    protected $description = 'Publish Block Builder assets and create project block directories';

    public function handle(): int
    {
        $parameters = $this->option('force') ? ['--force' => true] : [];

        $this->call('vendor:publish', [...$parameters, '--tag' => 'block-editor-config']);
        $this->call('vendor:publish', [...$parameters, '--tag' => 'block-editor-assets']);

        foreach ([app_path('Blocks'), resource_path('views/blocks'), public_path(config('block-editor.preview_path'))] as $path) {
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
                $this->info("Created {$path}");
            }
        }

        $this->info('Block Builder is ready. Add your models to block-editor.allowed_models.');

        return self::SUCCESS;
    }
}
