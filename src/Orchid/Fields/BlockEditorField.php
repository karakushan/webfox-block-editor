<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Orchid\Fields;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\Field;
use Orchid\Screen\Contracts\Fieldable;

class BlockEditorField extends Field implements Fieldable
{
    /**
     * Model instance (any model with translatable content field).
     */
    protected ?Model $model = null;

    /**
     * Content field name (default: 'content').
     */
    protected string $contentField = 'content';

    /**
     * Set model instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return self
     */
    public function model(?Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set content field name.
     *
     * @param  string  $field
     * @return self
     */
    public function contentField(string $field): self
    {
        $this->contentField = $field;

        return $this;
    }

    /**
     * Set service model (backward compatibility).
     *
     * @param  mixed  $service
     * @return self
     */
    public function service($service): self
    {
        $this->model = $service instanceof Model ? $service : null;

        return $this;
    }

    /**
     * Render the field.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        // Pass model ID and class for Livewire serialization
        $modelId = $this->model?->getKey();
        $modelClass = $this->model ? get_class($this->model) : null;

        return view('block-editor::orchid.fields.block-editor', [
            'field' => $this,
            'model' => $this->model,
            'modelId' => $modelId,
            'modelClass' => $modelClass,
            'contentField' => $this->contentField,
        ]);
    }
}
