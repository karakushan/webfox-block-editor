@php
    $type = $block['type'] ?? null;
    if (!$type) {
        return;
    }

    $template = \Webfox\BlockEditor\BlockRegistry::template($type);
    if (!$template) {
        return;
    }
@endphp

@include($template, [
    'block' => $block,
    'data' => $block['data'] ?? [],
    'settings' => $block['settings'] ?? [],
    'model' => $model ?? null,
    'locale' => $locale ?? app()->getLocale(),
])
