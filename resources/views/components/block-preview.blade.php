@php
    use Illuminate\Support\Str;
    $blockType = $block['type'] ?? '';
    $blockName = \Webfox\BlockEditor\BlockRegistry::name($blockType);
    $preview = \Webfox\BlockEditor\BlockRegistry::preview($blockType);
    $blockData = $block['data'] ?? [];
@endphp

<div class="block-preview">
    @if ($preview)
        <img src="{{ asset($preview) }}" alt="{{ $blockName }}" class="w-full h-auto rounded border border-gray-300">
    @else
        <div class="bg-gray-100 rounded-lg p-4 border border-gray-300">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gray-300 rounded flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <div>
                    <div class="font-medium text-gray-900">{{ $blockName }}</div>
                    @if ($blockData && !empty($blockData))
                        <div class="text-sm text-gray-500 mt-1">
                            @if (isset($blockData['title']))
                                {{ Str::limit($blockData['title'], 50) }}
                            @else
                                Блок без даних
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
