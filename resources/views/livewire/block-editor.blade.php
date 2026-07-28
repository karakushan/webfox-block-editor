@php
    use Illuminate\Support\Str;
@endphp

<div class="block-editor" x-data="blockEditor()"
    x-on:block-error.window="console.error('Block Editor Error:', $event.detail.message)"
    x-on:block-added.window="console.log('Block added:', $event.detail)"
    x-on:block-saved.window="console.log('Block saved:', $event.detail)">
    <div class="block-editor__wrapper">

        {{-- Add Block Button --}}
        <div class="block-editor__add-button">
            <button type="button" @click="console.log('Add block button clicked'); showAddMenu = !showAddMenu"
                class="block-editor__add-button-btn">
                <svg class="block-editor__add-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Додати блок
            </button>

            {{-- Block Type Menu --}}
            <div x-show="showAddMenu" @click.away="showAddMenu = false" x-cloak class="block-editor__menu">
                @foreach ($this->availableBlocks as $type => $config)
                    <button type="button" wire:click="addBlock('{{ $type }}')"
                        @click="console.log('Block type clicked:', '{{ $type }}'); showAddMenu = false"
                        class="block-editor__menu-item">
                        @if ($config['preview'] ?? null)
                            <img src="{{ asset($config['preview']) }}" alt="{{ $config['name'] }}"
                                class="block-editor__menu-item-preview">
                        @else
                            <div class="block-editor__menu-item-placeholder">
                                <svg class="block-editor__menu-item-placeholder-icon" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        @endif
                        <div class="block-editor__menu-item-info">
                            <div class="block-editor__menu-item-name">{{ $config['name'] }}</div>
                            @if ($config['description'] ?? null)
                                <div class="block-editor__menu-item-description">{{ $config['description'] }}</div>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Blocks List --}}
        <div x-ref="blocksList" class="block-editor__list" @sortable:end="handleSortEnd">
            @foreach ($this->blocks as $index => $block)
                @php
                    $isOpen = $this->editingBlockIndex === $index;
                @endphp
                <div wire:key="block-{{ $block['id'] ?? $index }}" data-block-index="{{ $index }}"
                    class="block-item" x-data="{ open: @js($isOpen) }"
                    x-on:block-opened.window="if ($event.detail.index === {{ $index }}) open = true"
                    x-on:block-closed.window="if ($event.detail.index === {{ $index }}) open = false">
                    {{-- Block Header (Accordion) --}}
                    <div class="block-item__header"
                        @click="open = !open; $wire.editBlock(open ? {{ $index }} : null)" draggable="true"
                        @dragstart="handleDragStart($event, {{ $index }})"
                        @dragover.prevent="handleDragOver($event)" @drop="handleDrop($event, {{ $index }})"
                        @dragend="handleDragEnd">
                        {{-- Drag Handle --}}
                        <div class="block-item__drag-handle" @click.stop>
                            <svg class="block-item__drag-handle-icon" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>

                        {{-- Accordion Arrow --}}
                        <div class="block-item__arrow" :class="{ 'block-item__arrow--open': open }">
                            <svg class="block-item__arrow-icon" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </div>

                        {{-- Block Info --}}
                        <div class="block-item__info">
                            <div class="block-item__content">
                                @if ($config = \Webfox\BlockEditor\BlockRegistry::get($block['type'] ?? ''))
                                    @if ($config['preview'] ?? null)
                                        <img src="{{ asset($config['preview']) }}" alt="{{ $config['name'] }}"
                                            class="block-item__preview">
                                    @else
                                        <div class="block-item__preview-placeholder">
                                            <svg class="block-item__preview-placeholder-icon" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                    @endif
                                @endif
                                <div class="block-item__details">
                                    <div class="block-item__name">
                                        {{ \Webfox\BlockEditor\BlockRegistry::name($block['type'] ?? '') }}
                                    </div>
                                    <div class="block-item__description">
                                        @if (isset($block['data']['title']) && !empty($block['data']['title']))
                                            {{ Str::limit($block['data']['title'], 50) }}
                                        @else
                                            Блок без даних
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="block-item__actions" @click.stop>
                                <button type="button"
                                    @click="if(confirm('Видалити цей блок?')) $wire.deleteBlock({{ $index }})"
                                    class="block-item__delete-btn" title="Видалити блок">
                                    <svg class="block-item__delete-btn-icon" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Block Content (Accordion Body) --}}
                    <div x-show="open" x-collapse.duration.300ms class="block-item__body">
                        <div @close-block-editor.window="open = false; $wire.closeEditor()">
                            @livewire(
                                \Webfox\BlockEditor\BlockRegistry::component($block['type'] ?? ''),
                                [
                                    'blockData' => $block,
                                    'blockIndex' => $index,
                                    'locale' => $this->locale,
                                ],
                                key('block-edit-' . $index)
                            )
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($this->blocks->isEmpty())
            <div class="block-editor__empty">
                Немає блоків. Натисніть "Додати блок" щоб почати.
            </div>
        @endif

        {{-- Add Block Button (Bottom) --}}
        <div class="block-editor__add-button">
            <button type="button" @click="console.log('Add block button clicked'); showAddMenu = !showAddMenu"
                class="block-editor__add-button-btn">
                <svg class="block-editor__add-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Додати блок
            </button>

            {{-- Block Type Menu --}}
            <div x-show="showAddMenu" @click.away="showAddMenu = false" x-cloak class="block-editor__menu">
                @foreach ($this->availableBlocks as $type => $config)
                    <button type="button" wire:click="addBlock('{{ $type }}')"
                        @click="console.log('Block type clicked:', '{{ $type }}'); showAddMenu = false"
                        class="block-editor__menu-item">
                        @if ($config['preview'] ?? null)
                            <img src="{{ asset($config['preview']) }}" alt="{{ $config['name'] }}"
                                class="block-editor__menu-item-preview">
                        @else
                            <div class="block-editor__menu-item-placeholder">
                                <svg class="block-editor__menu-item-placeholder-icon" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        @endif
                        <div class="block-editor__menu-item-info">
                            <div class="block-editor__menu-item-name">{{ $config['name'] }}</div>
                            @if ($config['description'] ?? null)
                                <div class="block-editor__menu-item-description">{{ $config['description'] }}</div>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function blockEditor() {
            return {
                showAddMenu: false,
                draggedBlockIndex: null,
                draggedOverBlockIndex: null,

                init() {
                    console.log('BlockEditor Alpine.js initialized', {
                        modelId: @js($this->modelId),
                        modelClass: @js($this->modelClass),
                        locale: @js($this->locale),
                        availableBlocks: @js($this->availableBlocks),
                        blocksCount: @js($this->blocks->count()),
                    });
                },

                handleDragStart(event, blockIndex) {
                    this.draggedBlockIndex = blockIndex;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/html', event.target);
                    event.target.style.opacity = '0.5';
                },

                handleDragOver(event) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';

                    const blockElement = event.currentTarget.closest('[data-block-index]');
                    if (blockElement) {
                        this.draggedOverBlockIndex = parseInt(blockElement.dataset.blockIndex);
                    }
                },

                handleDrop(event, targetBlockIndex) {
                    event.preventDefault();

                    if (this.draggedBlockIndex !== null && this.draggedBlockIndex !== targetBlockIndex) {
                        const blocks = @js($this->blocks->toArray());
                        const fromIndex = this.draggedBlockIndex;
                        const toIndex = targetBlockIndex;

                        // Reorder array
                        const reordered = [...blocks];
                        const [removed] = reordered.splice(fromIndex, 1);
                        reordered.splice(toIndex, 0, removed);

                        // Create order map: map old indices to new order positions
                        const orderMap = {};
                        blocks.forEach((block, oldIndex) => {
                            // Find this block in reordered array
                            let newIndex = -1;
                            if (block.id) {
                                newIndex = reordered.findIndex(b => b.id === block.id);
                            }
                            if (newIndex === -1) {
                                // Fallback: find by position (for blocks without id)
                                if (oldIndex === fromIndex) {
                                    newIndex = toIndex;
                                } else if (oldIndex < fromIndex && oldIndex >= toIndex) {
                                    newIndex = oldIndex + 1;
                                } else if (oldIndex > fromIndex && oldIndex <= toIndex) {
                                    newIndex = oldIndex - 1;
                                } else {
                                    newIndex = oldIndex;
                                }
                            }
                            orderMap[oldIndex] = newIndex + 1;
                        });

                        @this.updateOrder(orderMap);
                    }

                    this.handleDragEnd();
                },

                handleDragEnd() {
                    this.draggedBlockIndex = null;
                    this.draggedOverBlockIndex = null;
                    document.querySelectorAll('[draggable="true"]').forEach(el => {
                        el.style.opacity = '1';
                    });
                },

                handleSortEnd(event) {
                    const blocks = Array.from(event.detail.items).map((item, index) => {
                        return parseInt(item.dataset.blockIndex);
                    });

                    const orderMap = {};
                    blocks.forEach((blockIndex, index) => {
                        orderMap[blockIndex] = index + 1;
                    });

                    @this.updateOrder(orderMap);
                }
            }
        }
    </script>
@endpush
