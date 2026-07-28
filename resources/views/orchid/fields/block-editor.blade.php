@php
    $currentLocale = request()->get('lang', app()->getLocale());
    $finalModelId = $modelId ?? ($model ? $model->getKey() : null);
    $finalModelClass = $modelClass ?? ($model ? get_class($model) : null);
@endphp

<div class="block-editor-field block-editor-field--full-width" data-controller="block-editor"
    data-block-editor-model-id="{{ $finalModelId }}" data-block-editor-model-class="{{ $finalModelClass }}"
    data-block-editor-content-field="{{ $contentField }}" data-block-editor-locale="{{ $currentLocale }}"
    data-block-editor-api-url="{{ url(config('platform.prefix', '/admin') . '/api/block-editor') }}"
    data-block-editor-csrf-token="{{ csrf_token() }}">
</div>
