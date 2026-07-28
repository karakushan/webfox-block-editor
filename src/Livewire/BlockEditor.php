<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Webfox\BlockEditor\BlockRegistry;

class BlockEditor extends Component
{
    /**
     * Model ID (for serialization).
     */
    public ?int $modelId = null;

    /**
     * Model class name (for resolving model).
     */
    public ?string $modelClass = null;

    /**
     * Content field name (default: 'content').
     */
    public string $contentField = 'content';

    /**
     * Current locale.
     */
    public ?string $locale = null;

    /**
     * Currently editing block index.
     */
    public ?int $editingBlockIndex = null;

    /**
     * Get model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getModel(): ?Model
    {
        if (! $this->modelId || ! $this->modelClass) {
            return null;
        }

        return $this->modelClass::find($this->modelId);
    }

    /**
     * Component listeners.
     *
     * @var array<string, string>
     */
    protected $listeners = [
        'block-save' => 'handleBlockSave',
        'block-delete' => 'handleBlockDelete',
    ];

    /**
     * Mount the component.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|null  $model
     * @param  int|null  $modelId
     * @param  string|null  $modelClass
     * @param  string  $contentField
     * @param  string|null  $locale
     * @return void
     */
    public function mount($model = null, ?int $modelId = null, ?string $modelClass = null, string $contentField = 'content', ?string $locale = null): void
    {
        // If model is provided directly, extract ID and class
        if ($model instanceof Model) {
            $this->modelId = $model->getKey();
            $this->modelClass = get_class($model);
        } elseif ($modelId && $modelClass) {
            // If we have ID and class, use them
            $this->modelId = $modelId;
            $this->modelClass = $modelClass;
        }

        $this->contentField = $contentField;
        $this->locale = $locale ?? request()->get('lang', config('locales.default', 'uk'));
    }

    /**
     * Get blocks for the model.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getBlocksProperty()
    {
        $model = $this->getModel();
        if (! $model) {
            return collect();
        }

        $content = $this->getContent($model);

        if (! is_array($content) || ! isset($content['blocks'])) {
            return collect();
        }

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
        })->sortBy('order')->values();

        return $blocks;
    }

    /**
     * Get content from model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return array<string, mixed>
     */
    protected function getContent(?Model $model = null): array
    {
        $model = $model ?? $this->getModel();
        if (! $model) {
            return [];
        }

        // Check if model uses Spatie Translatable
        if (method_exists($model, 'getTranslation')) {
            $content = $model->getTranslation($this->contentField, $this->locale, false);
        } else {
            $content = $model->getAttribute($this->contentField);
        }

        // Handle JSON string content
        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        return is_array($content) ? $content : [];
    }

    /**
     * Get available block types.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableBlocksProperty(): array
    {
        return BlockRegistry::all();
    }

    /**
     * Save blocks to model content.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return void
     */
    protected function saveBlocks(array $blocks): void
    {
        $model = $this->getModel();
        if (! $model) {
            return;
        }

        $content = $this->getContent($model);

        // Ensure blocks are sorted by order
        usort($blocks, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        $content['blocks'] = $blocks;

        // Check if model uses Spatie Translatable
        if (method_exists($model, 'setTranslation')) {
            $model->setTranslation($this->contentField, $this->locale, $content);
        } else {
            $model->setAttribute($this->contentField, $content);
        }

        $model->save();
    }

    /**
     * Handle block save event from BaseBlock.
     *
     * @param  array<string, mixed>  $data
     * @return void
     */
    public function handleBlockSave(array $data): void
    {
        $blockIndex = $data['blockIndex'] ?? null;
        $blockData = $data['blockData'] ?? [];
        $locale = $data['locale'] ?? $this->locale;

        if (empty($blockData)) {
            return;
        }

        $blocks = $this->blocks->toArray();

        // Calculate order if not set
        $order = $blockData['order'] ?? null;
        if ($order === null) {
            if (!empty($blocks)) {
                $orders = array_column($blocks, 'order');
                $order = !empty($orders) ? max($orders) + 1 : count($blocks) + 1;
            } else {
                $order = 1;
            }
        }
        $blockData['order'] = $order;

        if ($blockIndex !== null && isset($blocks[$blockIndex])) {
            $blocks[$blockIndex] = $blockData;
        } else {
            $blocks[] = $blockData;
        }

        $this->saveBlocks($blocks);

        // Find the index of saved block after sorting
        $savedIndex = null;
        foreach ($blocks as $index => $block) {
            if (($block['id'] ?? null) === $blockData['id']) {
                $savedIndex = $index;
                break;
            }
        }

        if ($savedIndex === null) {
            $savedIndex = count($blocks) - 1;
        }

        $this->editingBlockIndex = $savedIndex;
        $this->dispatch('block-saved', index: $savedIndex);
        $this->dispatch('block-opened', index: $savedIndex);
    }

    /**
     * Handle block delete event from BaseBlock.
     *
     * @param  array<string, mixed>  $data
     * @return void
     */
    public function handleBlockDelete(array $data): void
    {
        $blockIndex = $data['blockIndex'] ?? null;

        if ($blockIndex === null) {
            return;
        }

        $blocks = $this->blocks->toArray();

        if (isset($blocks[$blockIndex])) {
            unset($blocks[$blockIndex]);
            $blocks = array_values($blocks);

            // Reorder blocks after deletion
            foreach ($blocks as $i => &$block) {
                $block['order'] = $i + 1;
            }
            unset($block); // Break reference

            $this->saveBlocks($blocks);

            if ($this->editingBlockIndex === $blockIndex) {
                $this->editingBlockIndex = null;
            }

            $this->dispatch('block-deleted');
        }
    }

    /**
     * Add a new block.
     *
     * @param  string  $type
     * @return void
     */
    public function addBlock(string $type): void
    {
        Log::info('BlockEditor::addBlock called', [
            'type' => $type,
            'modelId' => $this->modelId,
            'modelClass' => $this->modelClass,
        ]);

        $model = $this->getModel();
        if (! $model) {
            Log::warning('BlockEditor: Model is not set', [
                'type' => $type,
                'modelId' => $this->modelId,
                'modelClass' => $this->modelClass,
            ]);
            $this->dispatch('block-error', message: 'Model is not set');
            return;
        }

        if (! BlockRegistry::has($type)) {
            Log::warning('BlockEditor: Block type not registered', ['type' => $type]);
            $this->dispatch('block-error', message: 'Block type not registered: ' . $type);
            return;
        }

        $blocks = $this->blocks->toArray();
        $maxOrder = 0;
        if (!empty($blocks)) {
            $orders = array_column($blocks, 'order');
            $maxOrder = !empty($orders) ? max($orders) : 0;
        }

        $newBlock = [
            'id' => 'block-' . uniqid(),
            'type' => $type,
            'order' => $maxOrder + 1,
            'settings' => BlockRegistry::defaultSettings($type),
            'data' => BlockRegistry::defaultData($type),
        ];

        $blocks[] = $newBlock;
        $this->saveBlocks($blocks);

        $newIndex = count($blocks) - 1;
        $this->editingBlockIndex = $newIndex;
        $this->dispatch('block-added', index: $newIndex);
        $this->dispatch('block-opened', index: $newIndex);
    }

    /**
     * Delete a block.
     *
     * @param  int  $index
     * @return void
     */
    public function deleteBlock(int $index): void
    {
        $blocks = $this->blocks->toArray();

        if (isset($blocks[$index])) {
            unset($blocks[$index]);
            $blocks = array_values($blocks); // Reindex array

            // Reorder blocks
            foreach ($blocks as $i => &$block) {
                $block['order'] = $i + 1;
            }
            unset($block); // Break reference

            $this->saveBlocks($blocks);

            if ($this->editingBlockIndex === $index) {
                $this->editingBlockIndex = null;
            }

            $this->dispatch('block-deleted');
        }
    }

    /**
     * Edit a block.
     *
     * @param  int|null  $index
     * @return void
     */
    public function editBlock(?int $index = null): void
    {
        $this->editingBlockIndex = $index;

        if ($index !== null) {
            $this->dispatch('block-opened', index: $index);
        } else {
            $this->dispatch('block-closed');
        }
    }

    /**
     * Close block editor.
     *
     * @return void
     */
    public function closeEditor(): void
    {
        $this->editingBlockIndex = null;
        $this->dispatch('block-closed');
    }

    /**
     * Update block order.
     *
     * @param  array<int, int>  $orderMap  Array of [oldIndex => newOrder]
     * @return void
     */
    public function updateOrder(array $orderMap): void
    {
        $blocks = $this->blocks->toArray();

        if (empty($blocks)) {
            return;
        }

        // Create array with new order
        $orderedBlocks = [];
        foreach ($orderMap as $oldIndex => $newOrder) {
            if (isset($blocks[$oldIndex])) {
                $orderedBlocks[] = [
                    'index' => $oldIndex,
                    'order' => (int) $newOrder,
                    'block' => $blocks[$oldIndex],
                ];
            }
        }

        // Sort by new order
        usort($orderedBlocks, fn($a, $b) => $a['order'] <=> $b['order']);

        // Rebuild blocks array with new order
        $reorderedBlocks = [];
        foreach ($orderedBlocks as $i => $item) {
            $block = $item['block'];
            $block['order'] = $i + 1;
            $reorderedBlocks[] = $block;
        }

        $this->saveBlocks($reorderedBlocks);
    }

    /**
     * Handle block saved event.
     *
     * @param  int  $index
     * @return void
     */
    public function handleBlockSaved(int $index): void
    {
        // Keep block open after saving
        $this->editingBlockIndex = $index;
        $this->dispatch('block-saved', index: $index);
        $this->dispatch('block-opened', index: $index);
    }

    /**
     * Handle block deleted event.
     *
     * @param  int  $index
     * @return void
     */
    public function handleBlockDeleted(int $index): void
    {
        if ($this->editingBlockIndex === $index) {
            $this->editingBlockIndex = null;
        }

        $this->dispatch('block-deleted');
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('block-editor::livewire.block-editor');
    }
}
