<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Livewire;

use Livewire\Component;
use Webfox\BlockEditor\BlockRegistry;

abstract class BaseBlock extends Component
{
    /**
     * Block index in blocks array.
     */
    public ?int $blockIndex = null;

    /**
     * Block data array.
     *
     * @var array<string, mixed>
     */
    public array $blockData = [];

    /**
     * Block settings.
     *
     * @var array<string, mixed>
     */
    public array $settings = [];

    /**
     * Block data.
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * Current locale.
     */
    public ?string $locale = null;

    /**
     * Block type.
     */
    public string $type;

    /**
     * Mount the component.
     *
     * @param  array<string, mixed>|null  $blockData
     * @param  int|null  $blockIndex
     * @param  string|null  $locale
     * @return void
     */
    public function mount(?array $blockData = null, ?int $blockIndex = null, ?string $locale = null): void
    {
        $this->locale = $locale ?? request()->get('lang', config('locales.default', 'uk'));
        $this->blockIndex = $blockIndex;
        $this->blockData = $blockData ?? [];

        if ($blockData && isset($blockData['type'])) {
            $this->type = $blockData['type'];
            $this->settings = $blockData['settings'] ?? BlockRegistry::defaultSettings($this->type);
            $this->data = $blockData['data'] ?? BlockRegistry::defaultData($this->type);
        } else {
            $this->type = $this->getBlockType();
            $this->settings = BlockRegistry::defaultSettings($this->type);
            $this->data = BlockRegistry::defaultData($this->type);
        }
    }

    /**
     * Get block type identifier.
     *
     * @return string
     */
    abstract protected function getBlockType(): string;

    /**
     * Save block data.
     *
     * @return void
     */
    public function save(): void
    {
        $this->validate($this->rules());

        // Calculate order if not set
        $order = $this->blockData['order'] ?? null;

        $blockData = [
            'id' => $this->blockData['id'] ?? 'block-' . uniqid(),
            'type' => $this->type,
            'order' => $order,
            'settings' => $this->settings,
            'data' => $this->data,
        ];

        // Dispatch event to parent BlockEditor component to handle saving
        $this->dispatch('block-save', [
            'blockIndex' => $this->blockIndex,
            'blockData' => $blockData,
            'locale' => $this->locale,
        ]);
    }

    /**
     * Delete block.
     *
     * @return void
     */
    public function delete(): void
    {
        if ($this->blockIndex === null) {
            return;
        }

        // Dispatch event to parent BlockEditor component to handle deletion
        $this->dispatch('block-delete', [
            'blockIndex' => $this->blockIndex,
            'locale' => $this->locale,
        ]);
    }

    /**
     * Get validation rules for block.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }
}
