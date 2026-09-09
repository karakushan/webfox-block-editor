<?php

declare(strict_types=1);

namespace Webfox\BlockEditor;

use Illuminate\Support\Facades\View;

class BlockRenderer
{
    /**
     * Render all blocks for a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $contentField
     * @param  string  $locale
     * @return string
     */
    public function renderBlocks($model, string $contentField = 'content', ?string $locale = null): string
    {
        if (! $model) {
            return '';
        }

        if ($locale === null) {
            $locale = app()->getLocale();
        }

        // Get blocks from model
        $content = $this->getModelContent($model, $contentField, $locale);
        $blocks = $content['blocks'] ?? [];

        // Debug logging
        \Log::debug('BlockRenderer: renderBlocks', [
            'model_id' => $model->id ?? null,
            'contentField' => $contentField,
            'locale' => $locale,
            'content_keys' => array_keys($content),
            'blocks_count' => count($blocks),
            'blocks' => $blocks,
        ]);

        if (empty($blocks)) {
            \Log::debug('BlockRenderer: No blocks found', [
                'content' => $content,
            ]);
            return '';
        }

        // Sort blocks by order
        usort($blocks, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        // Render each block
        $html = '';
        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block, $model, $locale);
        }

        return $html;
    }

    /**
     * Render a single block.
     *
     * @param  array<string, mixed>  $block
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return string
     */
    public function renderBlock(array $block, $model = null, ?string $locale = null): string
    {
        if (! $this->isBlockEnabled($block)) {
            return '';
        }

        $type = $block['type'] ?? null;
        
        \Log::debug('BlockRenderer: renderBlock', [
            'type' => $type,
            'block_keys' => array_keys($block),
        ]);

        if (! $type || ! BlockRegistry::has($type)) {
            \Log::warning('BlockRenderer: Block type not registered', [
                'type' => $type,
                'available_types' => array_keys(BlockRegistry::all()),
            ]);
            return '';
        }

        $template = BlockRegistry::template($type);
        if (! $template) {
            \Log::warning('BlockRenderer: Template not found for block', [
                'type' => $type,
            ]);
            return '';
        }

        \Log::debug('BlockRenderer: Rendering block', [
            'type' => $type,
            'template' => $template,
        ]);

        try {
            return View::make($template, [
                'block' => $block,
                'data' => $block['data'] ?? [],
                'settings' => $block['settings'] ?? [],
                'model' => $model,
                'locale' => $locale,
            ])->render();
        } catch (\Exception $e) {
            // Log error but don't break the page
            \Log::error('Failed to render block', [
                'type' => $type,
                'template' => $template,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return '';
        }
    }

    /**
     * Check whether a block should be visible on the frontend.
     *
     * Blocks created before the visibility switcher have no flag and remain enabled.
     */
    protected function isBlockEnabled(array $block): bool
    {
        $enabled = data_get($block, 'settings.enabled', true);

        if (is_bool($enabled)) {
            return $enabled;
        }

        if (is_numeric($enabled)) {
            return (float) $enabled !== 0.0;
        }

        if (is_string($enabled)) {
            return ! in_array(strtolower(trim($enabled)), ['0', 'false', 'off', 'no'], true);
        }

        return true;
    }

    /**
     * Get model content for a specific field and locale.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $contentField
     * @param  string  $locale
     * @return array<string, mixed>
     */
    protected function getModelContent($model, string $contentField, string $locale): array
    {
        // Handle Spatie Translatable
        if (method_exists($model, 'getTranslation')) {
            $content = $model->getTranslation($contentField, $locale, false);
        } else {
            $content = $model->getAttribute($contentField);
        }

        // Handle JSON string content
        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        // If content is null or not an array, return empty array
        if (! is_array($content)) {
            return [];
        }

        return $content;
    }
}
