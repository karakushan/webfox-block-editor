<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webfox\BlockEditor\BlockRegistry;

class BlockEditorController extends Controller
{
    /**
     * Get available block types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function available(): JsonResponse
    {
        $blocks = $this->resolveAvailableBlocks(request());

        // Process preview paths to use full URLs
        foreach ($blocks as $type => &$config) {
            if (isset($config['preview']) && $config['preview'] !== null) {
                $config['preview'] = asset($config['preview']);
            }
        }

        $grouped = $this->groupBlocksByCategory($blocks);

        return response()->json([
            'success' => true,
            'blocks' => $blocks,
            'grouped' => $grouped,
        ]);
    }

    /**
     * Get blocks for a model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $modelId = $request->get('modelId');
        $modelClass = $request->get('modelClass');
        $contentField = $request->get('contentField', 'content');
        $locale = $request->get('locale', 'uk');

        if (! $modelId || ! $modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Model ID and class are required',
            ], 400);
        }

        if (! $this->isAllowedModel($modelClass)) {
            return $this->forbiddenModelResponse();
        }

        try {
            $model = $modelClass::find($modelId);

            if (! $model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model not found',
                ], 404);
            }

            $content = $this->getContent($model, $contentField, $locale);
            $blocks = $content['blocks'] ?? [];

            // Ensure blocks have id and order
            $blocks = collect($blocks)->map(function ($block, $index) {
                if (! isset($block['id'])) {
                    $block['id'] = 'block-' . ($index + 1);
                }
                if (! isset($block['order'])) {
                    $block['order'] = $index + 1;
                }
                return $block;
            })->sortBy('order')->values()->toArray();

            return response()->json([
                'success' => true,
                'blocks' => $blocks,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load blocks', [
                'error' => $e->getMessage(),
                'modelId' => $modelId,
                'modelClass' => $modelClass,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load blocks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a new block.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $modelId = $request->input('modelId');
        $modelClass = $request->input('modelClass');
        $contentField = $request->input('contentField', 'content');
        $locale = $request->input('locale', 'uk');
        $type = $request->input('type');
        $blockData = $request->input('blockData');

        if (! $modelId || ! $modelClass || ! $type) {
            return response()->json([
                'success' => false,
                'message' => 'Model ID, class, and block type are required',
            ], 400);
        }

        if (! $this->isAllowedModel($modelClass)) {
            return $this->forbiddenModelResponse();
        }

        if (! BlockRegistry::has($type)) {
            return response()->json([
                'success' => false,
                'message' => 'Block type not registered: ' . $type,
            ], 400);
        }

        try {
            $model = $modelClass::find($modelId);

            if (! $model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model not found',
                ], 404);
            }

            $content = $this->getContent($model, $contentField, $locale);
            $blocks = $content['blocks'] ?? [];

            $maxOrder = 0;
            if (! empty($blocks)) {
                $orders = array_column($blocks, 'order');
                $maxOrder = ! empty($orders) ? max($orders) : 0;
            }

            // Check if blockData is provided (for paste operation)
            if ($blockData) {
                // Paste operation - use provided data with new ID
                $newBlock = [
                    'id' => 'block-' . uniqid(),
                    'type' => $type,
                    'order' => $maxOrder + 1,
                    'settings' => $blockData['settings'] ?? BlockRegistry::defaultSettings($type),
                    'data' => $blockData['data'] ?? BlockRegistry::defaultData($type),
                ];
            } else {
                // Normal add operation - use defaults
                $newBlock = [
                    'id' => 'block-' . uniqid(),
                    'type' => $type,
                    'order' => $maxOrder + 1,
                    'settings' => BlockRegistry::defaultSettings($type),
                    'data' => BlockRegistry::defaultData($type),
                ];
            }

            $blocks[] = $newBlock;
            $content['blocks'] = $blocks;

            $this->saveContent($model, $contentField, $locale, $content);

            return response()->json([
                'success' => true,
                'block' => $newBlock,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add block', [
                'error' => $e->getMessage(),
                'modelId' => $modelId,
                'modelClass' => $modelClass,
                'type' => $type,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add block: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Paste a block after specified index.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pasteAfter(Request $request): JsonResponse
    {
        $modelId = $request->input('modelId');
        $modelClass = $request->input('modelClass');
        $contentField = $request->input('contentField', 'content');
        $locale = $request->input('locale', 'uk');
        $type = $request->input('type');
        $blockData = $request->input('blockData');
        $afterIndex = $request->input('afterIndex');

        if (! $modelId || ! $modelClass || ! $type || $afterIndex === null) {
            return response()->json([
                'success' => false,
                'message' => 'Model ID, class, block type, and afterIndex are required',
            ], 400);
        }

        if (! $this->isAllowedModel($modelClass)) {
            return $this->forbiddenModelResponse();
        }

        if (! BlockRegistry::has($type)) {
            return response()->json([
                'success' => false,
                'message' => 'Block type not registered: ' . $type,
            ], 400);
        }

        try {
            $model = $modelClass::find($modelId);

            if (! $model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model not found',
                ], 404);
            }

            $content = $this->getContent($model, $contentField, $locale);
            $blocks = $content['blocks'] ?? [];

            // Create new block
            $newBlock = [
                'id' => 'block-' . uniqid(),
                'type' => $type,
                'order' => 0, // Will be recalculated
                'settings' => $blockData['settings'] ?? BlockRegistry::defaultSettings($type),
                'data' => $blockData['data'] ?? BlockRegistry::defaultData($type),
            ];

            // Insert after specified index
            $insertIndex = $afterIndex + 1;
            array_splice($blocks, $insertIndex, 0, [$newBlock]);

            // Recalculate order
            foreach ($blocks as $i => &$block) {
                $block['order'] = $i + 1;
            }
            unset($block);

            $content['blocks'] = $blocks;
            $this->saveContent($model, $contentField, $locale, $content);

            return response()->json([
                'success' => true,
                'block' => $newBlock,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to paste block after', [
                'error' => $e->getMessage(),
                'modelId' => $modelId,
                'modelClass' => $modelClass,
                'type' => $type,
                'afterIndex' => $afterIndex,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to paste block: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a block.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $index
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $index): JsonResponse
    {
        $modelId = $request->input('modelId');
        $modelClass = $request->input('modelClass');
        $contentField = $request->input('contentField', 'content');
        $locale = $request->input('locale', 'uk');
        $blockData = $request->input('blockData');

        if (! $modelId || ! $modelClass || ! $blockData) {
            return response()->json([
                'success' => false,
                'message' => 'Model ID, class, and block data are required',
            ], 400);
        }

        if (! $this->isAllowedModel($modelClass)) {
            return $this->forbiddenModelResponse();
        }

        try {
            $model = $modelClass::find($modelId);

            if (! $model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model not found',
                ], 404);
            }

            $content = $this->getContent($model, $contentField, $locale);
            $blocks = $content['blocks'] ?? [];

            if (! isset($blocks[$index])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Block not found at index: ' . $index,
                ], 404);
            }

            // Calculate order if not set
            $order = $blockData['order'] ?? null;
            if ($order === null) {
                if (! empty($blocks)) {
                    $orders = array_column($blocks, 'order');
                    $order = ! empty($orders) ? max($orders) + 1 : count($blocks) + 1;
                } else {
                    $order = 1;
                }
            }
            $blockData['order'] = $order;

            $blocks[$index] = $blockData;
            $content['blocks'] = $blocks;

            $this->saveContent($model, $contentField, $locale, $content);

            return response()->json([
                'success' => true,
                'block' => $blockData,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update block', [
                'error' => $e->getMessage(),
                'modelId' => $modelId,
                'modelClass' => $modelClass,
                'index' => $index,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update block: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a block.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $index
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $index): JsonResponse
    {
        $modelId = $request->input('modelId');
        $modelClass = $request->input('modelClass');
        $contentField = $request->input('contentField', 'content');
        $locale = $request->input('locale', 'uk');

        if (! $modelId || ! $modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Model ID and class are required',
            ], 400);
        }

        if (! $this->isAllowedModel($modelClass)) {
            return $this->forbiddenModelResponse();
        }

        try {
            $model = $modelClass::find($modelId);

            if (! $model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model not found',
                ], 404);
            }

            $content = $this->getContent($model, $contentField, $locale);
            $blocks = $content['blocks'] ?? [];

            if (! isset($blocks[$index])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Block not found at index: ' . $index,
                ], 404);
            }

            unset($blocks[$index]);
            $blocks = array_values($blocks); // Reindex array

            // Reorder blocks
            foreach ($blocks as $i => &$block) {
                $block['order'] = $i + 1;
            }
            unset($block); // Break reference

            $content['blocks'] = $blocks;
            $this->saveContent($model, $contentField, $locale, $content);

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete block', [
                'error' => $e->getMessage(),
                'modelId' => $modelId,
                'modelClass' => $modelClass,
                'index' => $index,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete block: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import blocks from JSON.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $modelId = $request->input('modelId');
        $modelClass = $request->input('modelClass');
        $contentField = $request->input('contentField', 'content');
        $locale = $request->input('locale', 'uk');
        $importedBlocks = $request->input('blocks');

        if (! $modelId || ! $modelClass || ! $importedBlocks || ! is_array($importedBlocks)) {
            return response()->json([
                'success' => false,
                'message' => 'Model ID, class, and blocks array are required',
            ], 400);
        }

        if (! $this->isAllowedModel($modelClass)) {
            return $this->forbiddenModelResponse();
        }

        try {
            $model = $modelClass::find($modelId);

            if (! $model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model not found',
                ], 404);
            }

            // Validate and normalize imported blocks
            $blocks = [];
            foreach ($importedBlocks as $i => $block) {
                if (! isset($block['type'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Block at index ' . $i . ' is missing type',
                    ], 400);
                }

                if (! BlockRegistry::has($block['type'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Block type not registered: ' . $block['type'] . ' at index ' . $i,
                    ], 400);
                }

                // Normalize block structure
                $normalizedBlock = [
                    'id' => $block['id'] ?? 'block-' . uniqid(),
                    'type' => $block['type'],
                    'order' => $i + 1,
                    'settings' => $block['settings'] ?? BlockRegistry::defaultSettings($block['type']),
                    'data' => $block['data'] ?? BlockRegistry::defaultData($block['type']),
                ];

                $blocks[] = $normalizedBlock;
            }

            $content = $this->getContent($model, $contentField, $locale);
            $content['blocks'] = $blocks;

            $this->saveContent($model, $contentField, $locale, $content);

            return response()->json([
                'success' => true,
                'blocks' => $blocks,
                'count' => count($blocks),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to import blocks', [
                'error' => $e->getMessage(),
                'modelId' => $modelId,
                'modelClass' => $modelClass,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import blocks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder blocks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $modelId = $request->input('modelId');
        $modelClass = $request->input('modelClass');
        $contentField = $request->input('contentField', 'content');
        $locale = $request->input('locale', 'uk');
        $fromIndex = $request->input('fromIndex');
        $toIndex = $request->input('toIndex');

        if (! $modelId || ! $modelClass || $fromIndex === null || $toIndex === null) {
            return response()->json([
                'success' => false,
                'message' => 'Model ID, class, fromIndex, and toIndex are required',
            ], 400);
        }

        if (! $this->isAllowedModel($modelClass)) {
            return $this->forbiddenModelResponse();
        }

        try {
            $model = $modelClass::find($modelId);

            if (! $model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model not found',
                ], 404);
            }

            $content = $this->getContent($model, $contentField, $locale);
            $blocks = $content['blocks'] ?? [];

            if (empty($blocks)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No blocks to reorder',
                ], 400);
            }

            // Reorder array
            $reordered = [...$blocks];
            $removed = array_splice($reordered, $fromIndex, 1);
            array_splice($reordered, $toIndex, 0, $removed);

            // Rebuild blocks array with new order
            $reorderedBlocks = [];
            foreach ($reordered as $i => $block) {
                $block['order'] = $i + 1;
                $reorderedBlocks[] = $block;
            }

            $content['blocks'] = $reorderedBlocks;
            $this->saveContent($model, $contentField, $locale, $content);

            return response()->json([
                'success' => true,
                'blocks' => $reorderedBlocks,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reorder blocks', [
                'error' => $e->getMessage(),
                'modelId' => $modelId,
                'modelClass' => $modelClass,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder blocks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get content from model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $contentField
     * @param  string  $locale
     * @return array<string, mixed>
     */
    protected function getContent($model, string $contentField, string $locale): array
    {
        // Check if model uses Spatie Translatable
        if (method_exists($model, 'getTranslation')) {
            $content = $model->getTranslation($contentField, $locale, false);
        } else {
            $content = $model->getAttribute($contentField);
        }

        // Handle JSON string content
        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        return is_array($content) ? $content : [];
    }

    /**
     * Save content to model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $contentField
     * @param  string  $locale
     * @param  array<string, mixed>  $content
     * @return void
     */
    protected function saveContent($model, string $contentField, string $locale, array $content): void
    {
        // Check if model uses Spatie Translatable
        if (method_exists($model, 'setTranslation')) {
            $model->setTranslation($contentField, $locale, $content);
        } else {
            $model->setAttribute($contentField, $content);
        }

        $model->save();
    }

    /**
     * Resolve available blocks with optional model context.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function resolveAvailableBlocks(Request $request): array
    {
        $blocks = BlockRegistry::all();
        $model = $this->resolveContextModel($request);
        $locale = $request->get('locale', 'uk');

        foreach ($blocks as $type => &$config) {
            $resolverClass = $config['resolver_class'] ?? null;

            if ($resolverClass && class_exists($resolverClass) && method_exists($resolverClass, 'resolveEditorConfig')) {
                $resolvedConfig = $resolverClass::resolveEditorConfig($model, $locale);

                if (is_array($resolvedConfig)) {
                    $config = array_merge($config, $resolvedConfig);
                }
            }

            unset($config['resolver_class']);
        }

        return $blocks;
    }

    /**
     * Resolve context model from request.
     */
    protected function resolveContextModel(Request $request): ?object
    {
        $modelId = $request->get('modelId');
        $modelClass = $request->get('modelClass');

        if (! $modelId || ! $modelClass || ! $this->isAllowedModel($modelClass)) {
            return null;
        }

        return $modelClass::find($modelId);
    }

    protected function isAllowedModel(mixed $modelClass): bool
    {
        return is_string($modelClass)
            && class_exists($modelClass)
            && in_array($modelClass, config('block-editor.allowed_models', []), true);
    }

    protected function forbiddenModelResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The requested model is not enabled for Block Builder.',
        ], 403);
    }

    /**
     * Group blocks by category.
     *
     * @param  array<string, array<string, mixed>>  $blocks
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function groupBlocksByCategory(array $blocks): array
    {
        $grouped = [];

        foreach ($blocks as $type => $config) {
            $category = $config['category'] ?? 'Общее';
            $grouped[$category] ??= [];
            $grouped[$category][$type] = $config;
        }

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
}
