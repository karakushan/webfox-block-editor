<?php

declare(strict_types=1);

namespace Webfox\BlockEditor;

class BlockRegistry
{
    /**
     * Registered blocks.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $blocks = [];

    /**
     * Register a new block type.
     *
     * @param  string  $type  Block type identifier
     * @param  array<string, mixed>  $config  Block configuration
     * @return void
     */
    public static function register(string $type, array $config): void
    {
        static::$blocks[$type] = array_merge([
            'name' => $type,
            'description' => '',
            'category' => 'Общее',
            'component' => null,
            'preview' => null,
            'template' => null,
            'fields' => [],
            'default_settings' => [],
            'default_data' => [],
        ], $config);
    }

    /**
     * Get block configuration by type.
     *
     * @param  string  $type  Block type identifier
     * @return array<string, mixed>|null
     */
    public static function get(string $type): ?array
    {
        return static::$blocks[$type] ?? null;
    }

    /**
     * Get all registered blocks.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return static::$blocks;
    }

    /**
     * Check if block type is registered.
     *
     * @param  string  $type  Block type identifier
     * @return bool
     */
    public static function has(string $type): bool
    {
        return isset(static::$blocks[$type]);
    }

    /**
     * Get block name by type.
     *
     * @param  string  $type  Block type identifier
     * @return string
     */
    public static function name(string $type): string
    {
        return static::$blocks[$type]['name'] ?? $type;
    }

    /**
     * Get block component class by type.
     *
     * @param  string  $type  Block type identifier
     * @return string|null
     */
    public static function component(string $type): ?string
    {
        return static::$blocks[$type]['component'] ?? null;
    }

    /**
     * Get default settings for block type.
     *
     * @param  string  $type  Block type identifier
     * @return array<string, mixed>
     */
    public static function defaultSettings(string $type): array
    {
        return static::$blocks[$type]['default_settings'] ?? [];
    }

    /**
     * Get default data for block type.
     *
     * @param  string  $type  Block type identifier
     * @return array<string, mixed>
     */
    public static function defaultData(string $type): array
    {
        return static::$blocks[$type]['default_data'] ?? [];
    }

    /**
     * Get preview path for block type.
     *
     * @param  string  $type  Block type identifier
     * @return string|null
     */
    public static function preview(string $type): ?string
    {
        return static::$blocks[$type]['preview'] ?? null;
    }

    /**
     * Get category for a registered block.
     *
     * @param  string  $type
     * @return string
     */
    public static function category(string $type): string
    {
        return static::get($type)['category'] ?? 'Общее';
    }

    /**
     * Get all blocks grouped by category.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];

        foreach (static::$blocks as $type => $config) {
            $category = $config['category'] ?? 'Общее';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$type] = $config;
        }

        // Sort categories, "Общее" first
        uksort($grouped, function ($a, $b) {
            if ($a === 'Общее') {
                return -1;
            }
            if ($b === 'Общее') {
                return 1;
            }
            return strcmp($a, $b);
        });

        return $grouped;
    }

    /**
     * Get fields for a registered block.
     *
     * @param  string  $type
     * @return array<string, array<string, mixed>>
     */
    public static function fields(string $type): array
    {
        return static::get($type)['fields'] ?? [];
    }

    /**
     * Get template path for a registered block.
     *
     * @param  string  $type
     * @return string|null
     */
    public static function template(string $type): ?string
    {
        return static::get($type)['template'] ?? null;
    }
}
