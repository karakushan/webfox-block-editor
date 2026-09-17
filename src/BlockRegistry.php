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
        $fields = $config['fields'] ?? [];
        $fields['settings'] = array_merge(
            static::spacingFields(),
            $fields['settings'] ?? [],
        );

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
        ], $config, [
            'fields' => $fields,
            'default_settings' => array_merge(
                static::defaultSpacingSettings($type),
                $config['default_settings'] ?? [],
            ),
        ]);
    }

    /**
     * Get the settings fields shared by every block.
     *
     * The editor groups these fields into responsive controls, while the
     * values remain flat for backwards compatibility with existing blocks.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function spacingFields(): array
    {
        return [
            'padding_top_desktop' => [
                'type' => 'number',
                'label' => 'Відступ зверху',
                'required' => false,
                'placeholder' => '0',
                'min' => 0,
                'step' => 1,
            ],
            'padding_bottom_desktop' => [
                'type' => 'number',
                'label' => 'Відступ знизу',
                'required' => false,
                'placeholder' => '0',
                'min' => 0,
                'step' => 1,
            ],
            'padding_top_mobile' => [
                'type' => 'number',
                'label' => 'Відступ зверху',
                'required' => false,
                'placeholder' => '0',
                'min' => 0,
                'step' => 1,
            ],
            'padding_bottom_mobile' => [
                'type' => 'number',
                'label' => 'Відступ знизу',
                'required' => false,
                'placeholder' => '0',
                'min' => 0,
                'step' => 1,
            ],
        ];
    }

    /**
     * Get default responsive spacing values for a block.
     *
     * @return array<string, int|null>
     */
    public static function defaultSpacingSettings(?string $type = null): array
    {
        $defaults = [
            'padding_top_desktop' => null,
            'padding_bottom_desktop' => null,
            'padding_top_mobile' => null,
            'padding_bottom_mobile' => null,
        ];

        $legacyDefaults = [
            'about-hero' => [80, 80, 32, 32],
            'about-reasons' => [72, 72, 48, 48],
            'about-statistics' => [80, 80, 48, 48],
            'about-summary' => [96, 96, 48, 48],
            'about-trusted' => [96, 96, 48, 48],
            'about-who-we-are' => [96, 96, 48, 48],
            'case-goals-client' => [96, 96, 64, 64],
            'case-hero' => [12, 48, 12, 0],
            'case-internal-pages' => [96, 96, 0, 48],
            'case-mobile-version' => [96, 96, 48, 48],
            'case-navigation' => [24, 24, 24, 24],
            'case-other-projects' => [96, 96, 64, 64],
            'case-results' => [72, 72, 72, 72],
            'case-screen-preview' => [96, 96, 0, 0],
            'case-workflow' => [96, 96, 64, 64],
            'cases-filter' => [0, 0, 0, 64],
            'cases-grid' => [0, 96, 0, 64],
            'cases-hero' => [12, 48, 12, 64],
            'contact-faq' => [96, 96, 48, 96],
            'contact-hero' => [96, 72, 24, 72],
            'contact-location-map' => [0, 80, 0, 0],
            'home-advantages' => [128, 128, 80, 80],
            'home-brand-lead' => [0, 0, 48, 0],
            'home-cta' => [64, 96, 64, 64],
            'home-hero' => [0, 0, 48, 0],
            'home-growth-tags' => [116, 40, 88, 40],
            'home-portfolio' => [96, 96, 64, 64],
            'home-service-categories' => [16, 16, 6, 6],
            'home-services' => [48, 48, 48, 48],
            'map' => [72, 72, 48, 48],
            'service-ad-examples' => [96, 96, 64, 64],
            'service-ad-results' => [96, 96, 64, 64],
            'service-additional-costs-block' => [64, 64, 48, 48],
            'service-advantages-block' => [64, 64, 48, 48],
            'service-child-services-tags-block' => [48, 48, 48, 48],
            'service-cta-block' => [24, 24, 24, 24],
            'service-cta-header-block' => [96, 96, 24, 24],
            'service-language-groups-block' => [96, 96, 64, 64],
            'service-other-services-block' => [96, 96, 24, 24],
            'service-portfolio-block' => [96, 96, 48, 48],
            'service-prices-block' => [64, 64, 48, 48],
            'service-process-block' => [96, 96, 48, 48],
            'service-promotion-factors-block' => [96, 64, 24, 24],
            'service-reasons-block' => [96, 96, 48, 48],
            'service-results-numbers-block' => [64, 64, 48, 48],
            'service-specialists-block' => [64, 64, 48, 48],
            'service-subservices-block' => [96, 96, 64, 64],
            'service-tags-block' => [48, 48, 48, 48],
            'service-technologies-block' => [96, 96, 48, 48],
            'service-terms-block' => [64, 64, 48, 48],
            'service-text-block' => [48, 48, 24, 24],
            'service-top-results-block' => [64, 64, 48, 48],
            'service-translation-services-block' => [64, 64, 48, 48],
            'service-trusted-block' => [72, 72, 48, 48],
            'service-why-choose-block' => [96, 96, 48, 48],
            'link-placement-table' => [48, 48, 48, 48],
        ];

        if ($type === null || ! isset($legacyDefaults[$type])) {
            return $defaults;
        }

        [$desktopTop, $desktopBottom, $mobileTop, $mobileBottom] = $legacyDefaults[$type];

        return array_merge($defaults, [
            'padding_top_desktop' => $desktopTop,
            'padding_bottom_desktop' => $desktopBottom,
            'padding_top_mobile' => $mobileTop,
            'padding_bottom_mobile' => $mobileBottom,
        ]);
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
     * Merge persisted block settings with defaults without allowing empty
     * spacing values to erase a legacy default.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function resolveSettings(string $type, array $settings): array
    {
        $resolved = array_merge(static::defaultSettings($type), $settings);

        foreach (array_keys(static::spacingFields()) as $field) {
            $value = $settings[$field] ?? null;

            if (! is_numeric($value) || (string) $value === '') {
                $resolved[$field] = static::defaultSettings($type)[$field] ?? null;
            }
        }

        return $resolved;
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
