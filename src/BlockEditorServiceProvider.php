<?php

declare(strict_types=1);

namespace Webfox\BlockEditor;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;
use Webfox\BlockEditor\Console\Commands\InstallBlockBuilderCommand;
use Webfox\BlockEditor\Console\Commands\MakeBlockCommand;
use Webfox\BlockEditor\Http\Controllers\BlockEditorController;
use Webfox\BlockEditor\Http\Controllers\ImageUploadController;

class BlockEditorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/block-editor.php', 'block-editor');

        $this->app->singleton('block-editor.renderer', fn () => new BlockRenderer());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([MakeBlockCommand::class, InstallBlockBuilderCommand::class]);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'block-editor');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/block-editor'),
        ], 'block-editor-views');

        $this->publishes([
            __DIR__ . '/../config/block-editor.php' => config_path('block-editor.php'),
        ], 'block-editor-config');

        $this->publishes([
            __DIR__ . '/../resources/dist/block-editor.css' => public_path('vendor/webfox-block-builder/block-editor.css'),
            __DIR__ . '/../resources/dist/block-editor.js' => public_path('vendor/webfox-block-builder/block-editor.js'),
        ], 'block-editor-assets');

        $this->registerAssets();
        $this->registerRoutes();
        $this->registerBladeComponents();
        $this->registerBlocks();
    }

    protected function registerAssets(): void
    {
        $this->app->booted(function (): void {
            $cssPath = '/vendor/webfox-block-builder/block-editor.css';
            $jsPath = '/vendor/webfox-block-builder/block-editor.js';
            $stylesheets = config('platform.resource.stylesheets', []);
            $scripts = config('platform.resource.scripts', []);

            if (! in_array($cssPath, $stylesheets, true)) {
                $this->app['config']->set('platform.resource.stylesheets', [...$stylesheets, $cssPath]);
            }

            if (! in_array($jsPath, $scripts, true)) {
                $this->app['config']->set('platform.resource.scripts', [...$scripts, $jsPath]);
            }
        });
    }

    protected function registerRoutes(): void
    {
        Route::prefix(config('platform.prefix', '/admin') . '/api/block-editor')
            ->middleware(['web', 'platform'])
            ->group(function (): void {
                Route::get('/blocks/available', [BlockEditorController::class, 'available'])->name('block-editor.blocks.available');
                Route::get('/blocks', [BlockEditorController::class, 'index'])->name('block-editor.blocks.index');
                Route::post('/blocks', [BlockEditorController::class, 'store'])->name('block-editor.blocks.store');
                Route::post('/blocks/paste-after', [BlockEditorController::class, 'pasteAfter'])->name('block-editor.blocks.paste-after');
                Route::post('/blocks/paste-all', [BlockEditorController::class, 'pasteAll'])->name('block-editor.blocks.paste-all');
                Route::post('/blocks/delete-all', [BlockEditorController::class, 'deleteAll'])->name('block-editor.blocks.delete-all');
                Route::put('/blocks/{index}', [BlockEditorController::class, 'update'])->name('block-editor.blocks.update');
                Route::delete('/blocks/{index}', [BlockEditorController::class, 'destroy'])->name('block-editor.blocks.destroy');
                Route::post('/blocks/reorder', [BlockEditorController::class, 'reorder'])->name('block-editor.blocks.reorder');
                Route::post('/blocks/import', [BlockEditorController::class, 'import'])->name('block-editor.blocks.import');
                Route::post('/images/upload', [ImageUploadController::class, 'upload'])->name('block-editor.images.upload');
                Route::delete('/images/delete', [ImageUploadController::class, 'delete'])->name('block-editor.images.delete');
                Route::post('/images/upload-tinymce', [ImageUploadController::class, 'uploadForTinyMCE'])->name('block-editor.images.upload-tinymce');
            });
    }

    protected function registerBladeComponents(): void
    {
        Blade::component('block-editor::components.block-renderer', 'block');
        Blade::directive('blocks', fn (string $expression): string => "<?php echo app('block-editor.renderer')->renderBlocks({$expression}); ?>");
    }

    protected function registerBlocks(): void
    {
        $path = config('block-editor.blocks_path');
        $namespace = trim((string) config('block-editor.blocks_namespace', 'App\\Blocks'), '\\');

        if (! is_string($path) || ! is_dir($path)) {
            return;
        }

        foreach ((new Finder())->files()->name(config('block-editor.blocks_pattern', '*Block.php'))->in($path) as $file) {
            $relativeClass = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = $namespace . '\\' . $relativeClass;

            if (class_exists($class) && method_exists($class, 'register')) {
                $class::register();
            }
        }
    }
}
