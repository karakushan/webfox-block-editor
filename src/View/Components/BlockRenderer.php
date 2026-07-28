<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\View\Components;

use Illuminate\View\Component;
use Webfox\BlockEditor\BlockRegistry;

class BlockRenderer extends Component
{
    /**
     * Block data.
     *
     * @var array<string, mixed>
     */
    public array $block;

    /**
     * Create a new component instance.
     *
     * @param  array<string, mixed>  $block
     */
    public function __construct(array $block)
    {
        $this->block = $block;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $type = $this->block['type'] ?? null;
        if (! $type) {
            return '';
        }

        $template = BlockRegistry::template($type);
        if (! $template) {
            return '';
        }

        return view($template, [
            'block' => $this->block,
            'data' => $this->block['data'] ?? [],
            'settings' => $this->block['settings'] ?? [],
        ]);
    }
}

