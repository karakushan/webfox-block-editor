<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Livewire\Blocks;

use Webfox\BlockEditor\Livewire\BaseBlock;

class HeroBlock extends BaseBlock
{
    /**
     * Get block type identifier.
     *
     * @return string
     */
    protected function getBlockType(): string
    {
        return 'hero';
    }

    /**
     * Get validation rules for block.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'data.title' => 'nullable|string|max:255',
            'data.subtitle' => 'nullable|string|max:500',
            'data.image' => 'nullable|string',
            'data.cta_text' => 'nullable|string|max:100',
            'data.cta_url' => 'nullable|string|max:255',
            'settings.background_color' => 'nullable|string|max:7',
            'settings.text_color' => 'nullable|string|max:7',
        ];
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('block-editor::livewire.blocks.hero-block');
    }
}

