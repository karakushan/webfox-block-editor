<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:block
                            {name : The name of the block}
                            {--category=Общее : The category of the block}
                            {--description= : The description of the block}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new block class and register it';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $category = $this->option('category') ?? 'Общее';
        $description = $this->option('description') ?? '';

        $blockType = Str::kebab($name);
        $className = Str::studly($name) . 'Block';
        $namespace = 'App\\Blocks';

        // Create directory if it doesn't exist
        $directory = app_path('Blocks');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $this->error("Block class [{$className}] already exists!");
            return Command::FAILURE;
        }

        // Generate block class
        $stub = $this->getStub();
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);
        $stub = str_replace('{{ blockType }}', $blockType, $stub);
        $stub = str_replace('{{ blockName }}', Str::title(str_replace('-', ' ', $blockType)), $stub);
        $stub = str_replace('{{ description }}', $description ?: 'Block description', $stub);
        $stub = str_replace('{{ category }}', $category, $stub);

        file_put_contents($filePath, $stub);

        $this->info("Block class [{$className}] created successfully!");

        // Create block template
        $this->createBlockTemplate($blockType, $className);

        $this->info("Block [{$blockType}] will be discovered automatically.");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("1. Edit the block class at: {$filePath}");
        $this->line("2. Define fields in the getFields() method");
        $this->line("3. Clear config cache: php artisan config:clear");

        return Command::SUCCESS;
    }

    /**
     * Get the stub file for the block class.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Webfox\BlockEditor\BlockRegistry;

class {{ class }}
{
    /**
     * Register the block.
     *
     * @return void
     */
    public static function register(): void
    {
        BlockRegistry::register('{{ blockType }}', [
            'name' => '{{ blockName }}',
            'description' => '{{ description }}',
            'category' => '{{ category }}',
            'component' => null,
            'preview' => null,
            'template' => 'blocks.{{ blockType }}',
            'fields' => static::getFields(),
            'default_settings' => static::getDefaultSettings(),
            'default_data' => static::getDefaultData(),
        ]);
    }

    /**
     * Get fields configuration for the block.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getFields(): array
    {
        return [
            'data' => [
                // Example field configuration:
                // 'title' => [
                //     'type' => 'text',
                //     'label' => 'Заголовок',
                //     'required' => true,
                //     'placeholder' => 'Введіть заголовок',
                // ],
                // 'content' => [
                //     'type' => 'textarea',
                //     'label' => 'Контент',
                //     'required' => false,
                //     'rows' => 5,
                // ],
            ],
            'settings' => [
                // Example settings configuration:
                // 'background_color' => [
                //     'type' => 'color',
                //     'label' => 'Колір фону',
                //     'required' => false,
                // ],
            ],
        ];
    }

    /**
     * Get default settings for the block.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultSettings(): array
    {
        return [
            // Add default settings here
        ];
    }

    /**
     * Get default data for the block.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultData(): array
    {
        return [
            // Add default data here based on fields
        ];
    }
}
PHP;
    }

    /**
     * Create block template file.
     *
     * @param  string  $blockType
     * @param  string  $className
     * @return void
     */
    protected function createBlockTemplate(string $blockType, string $className): void
    {
        $viewsDirectory = resource_path('views/blocks');
        if (!is_dir($viewsDirectory)) {
            mkdir($viewsDirectory, 0755, true);
        }

        $templatePath = $viewsDirectory . '/' . $blockType . '.blade.php';

        if (file_exists($templatePath)) {
            $this->warn("Template [{$templatePath}] already exists!");
            return;
        }

        $templateStub = <<<'BLADE'
{{-- {{ blockName }} Block Template --}}

@php
    $data = $data ?? [];
    $settings = $settings ?? [];
@endphp

<div class="block block--{{ blockType }}">
    {{-- Add your block HTML here --}}
    <h2>{{ $data['title'] ?? '{{ blockName }}' }}</h2>
    @if(isset($data['content']))
        <div class="block__content">
            {!! $data['content'] !!}
        </div>
    @endif
</div>
BLADE;

        $templateStub = str_replace('{{ blockName }}', Str::title(str_replace('-', ' ', $blockType)), $templateStub);
        $templateStub = str_replace('{{ blockType }}', $blockType, $templateStub);

        file_put_contents($templatePath, $templateStub);

        $this->info("Block template [{$templatePath}] created successfully!");
    }
}
