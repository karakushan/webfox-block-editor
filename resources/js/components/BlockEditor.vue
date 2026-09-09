    <template>
        <div class="block-editor">
            <!-- Copy Tooltip -->
            <Teleport to="body">
                <div
                    v-if="copyTooltip.visible"
                    class="block-editor__copy-tooltip"
                    :style="{ top: copyTooltip.y + 'px', left: copyTooltip.x + 'px' }"
                >
                    <svg class="block-editor__copy-tooltip-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Блок скопійовано
                </div>
            </Teleport>

            <!-- Image Preview Tooltip -->
            <Teleport to="body">
                <div
                    v-if="previewTooltip.visible && previewTooltip.src"
                    class="block-editor__preview-tooltip"
                    :style="{ top: previewTooltip.y + 'px', left: previewTooltip.x + 'px' }"
                >
                    <img :src="previewTooltip.src" :alt="previewTooltip.alt" class="block-editor__preview-tooltip-image" />
                </div>
            </Teleport>

            <!-- Sidebar Overlay -->
        <div v-if="showSidebar" class="block-editor__overlay" @click="showSidebar = false"></div>

        <!-- Sidebar -->
        <div class="block-editor__sidebar" :class="{ 'block-editor__sidebar--open': showSidebar }">
            <div class="block-editor__sidebar-header">
                <h3 class="block-editor__sidebar-title">Додати блок</h3>
                <button type="button" @click="showSidebar = false" class="block-editor__sidebar-close">
                    <svg class="block-editor__sidebar-close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="block-editor__sidebar-search">
                <div class="block-editor__search-wrapper">
                    <svg class="block-editor__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input v-model="searchQuery" type="text" placeholder="Пошук блоків..."
                        class="block-editor__search-input" />
                    <button v-if="searchQuery" type="button" @click="searchQuery = ''"
                        class="block-editor__search-clear">
                        <svg class="block-editor__search-clear-icon" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="block-editor__sidebar-content">
                <div v-for="(blocksInCategory, category) in filteredGroupedBlocks" :key="category"
                    class="block-editor__category">
                    <div class="block-editor__category-header">
                        <h4 class="block-editor__category-title">{{ category }}</h4>
                        <span class="block-editor__category-count">
                            {{ Object.keys(blocksInCategory).length }}
                        </span>
                    </div>
                    <div class="block-editor__blocks-list">
                        <button v-for="(config, type) in blocksInCategory" :key="type" type="button"
                            @click="addBlock(type)" class="block-editor__block-item">
                            <div class="block-editor__block-item-preview">
                                <img v-if="config.preview" :src="config.preview" :alt="config.name"
                                    class="block-editor__block-item-image"
                                    @mouseenter="showPreviewTooltip($event, config.preview, config.name)"
                                    @mouseleave="hidePreviewTooltip"
                                    @mousemove="updateTooltipPosition($event)" />
                                <div v-else class="block-editor__block-item-placeholder">
                                    <svg class="block-editor__block-item-placeholder-icon" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="block-editor__block-item-info">
                                <div class="block-editor__block-item-name">
                                    {{ config.name }}
                                </div>
                                <div v-if="config.description" class="block-editor__block-item-description">
                                    {{ config.description }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <div v-if="Object.keys(filteredGroupedBlocks).length === 0" class="block-editor__no-results">
                    Блоки не знайдено
                </div>
            </div>
        </div>

        <div class="block-editor__wrapper">
            <!-- Action Buttons -->
            <div class="block-editor__actions">
                <div class="block-editor__add-button">
                    <button type="button" @click="showSidebar = true" class="block-editor__add-button-btn">
                        <svg class="block-editor__add-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Додати блок
                    </button>
                </div>

                <div class="block-editor__export-import">
                    <button type="button" @click="exportBlocks" class="block-editor__action-btn block-editor__action-btn--export" title="Експорт блоків">
                        <svg class="block-editor__action-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Експорт
                    </button>

                    <button type="button" @click="triggerImport" class="block-editor__action-btn block-editor__action-btn--import" title="Імпорт блоків">
                        <svg class="block-editor__action-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Імпорт
                    </button>

                    <input ref="fileInput" type="file" accept=".json" @change="importBlocks" class="block-editor__file-input" />
                </div>
            </div>

            <!-- Blocks List -->
            <div class="block-editor__list">
                <div v-for="(block, index) in blocks" :key="block.id || index" :data-block-index="index"
                    class="block-item" :class="{ 'block-item--disabled': !isBlockEnabled(block) }">
                    <!-- Block Header (Accordion) -->
                    <div class="block-item__header" @click="toggleBlock(index)" draggable="true"
                        @dragstart="handleDragStart($event, index)" @dragover.prevent="handleDragOver($event)"
                        @drop="handleDrop($event, index)" @dragend="handleDragEnd">
                        <!-- Drag Handle -->
                        <div class="block-item__drag-handle" @click.stop>
                            <svg class="block-item__drag-handle-icon" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>

                        <!-- Accordion Arrow -->
                        <div class="block-item__arrow" :class="{ 'block-item__arrow--open': openBlocks[index] }">
                            <svg class="block-item__arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </div>

                        <!-- Block Info -->
                        <div class="block-item__info">
                            <div class="block-item__content">
                                <img v-if="availableBlocks[block.type]?.preview"
                                    :src="availableBlocks[block.type].preview" :alt="availableBlocks[block.type].name"
                                    class="block-item__preview"
                                    @mouseenter="showPreviewTooltip($event, availableBlocks[block.type].preview, availableBlocks[block.type].name)"
                                    @mouseleave="hidePreviewTooltip"
                                    @mousemove="updateTooltipPosition($event)" />
                                <div v-else class="block-item__preview-placeholder">
                                    <svg class="block-item__preview-placeholder-icon" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="block-item__details">
                                    <div class="block-item__name">
                                        {{ availableBlocks[block.type]?.name || block.type }}
                                    </div>
                                    <div class="block-item__description">
                                        {{
                                            block.data?.title
                                                ? truncate(block.data.title, 50)
                                                : 'Блок без даних'
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="block-item__actions" @click.stop>
                                <button
                                    type="button"
                                    class="block-item__visibility-toggle"
                                    :class="{ 'block-item__visibility-toggle--disabled': !isBlockEnabled(block) }"
                                    :aria-pressed="isBlockEnabled(block)"
                                    :aria-label="isBlockEnabled(block) ? 'Вимкнути блок на сайті' : 'Увімкнути блок на сайті'"
                                    :title="isBlockEnabled(block) ? 'Блок увімкнений на сайті' : 'Блок вимкнений на сайті'"
                                    :disabled="visibilityUpdating[index]"
                                    @click.stop="toggleBlockVisibility(index)"
                                >
                                    <span class="block-item__visibility-track" aria-hidden="true">
                                        <span class="block-item__visibility-thumb"></span>
                                    </span>
                                    <span class="block-item__visibility-label">
                                        {{ isBlockEnabled(block) ? 'Вкл' : 'Викл' }}
                                    </span>
                                </button>
                                <button type="button" @click="copyBlock(index, $event)" class="block-item__copy-btn"
                                    title="Копіювати блок">
                                    <svg class="block-item__copy-btn-icon" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </button>
                                <button type="button" @click="pasteBlockAfter(index)" class="block-item__paste-after-btn"
                                    :disabled="!clipboardBlock" :title="clipboardBlock ? 'Вставити після цього блоку' : 'Спочатку скопіюйте блок'">
                                    <svg class="block-item__paste-after-btn-icon" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                        </path>
                                    </svg>
                                </button>
                                <button type="button" @click="deleteBlock(index)" class="block-item__delete-btn"
                                    title="Видалити блок">
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

                    <!-- Block Content (Accordion Body) -->
                    <div v-show="openBlocks[index]" class="block-item__body">
                        <block-form
                            :block="block"
                            :block-index="index"
                            :block-type="block.type"
                            :available-blocks="availableBlocks"
                            :api-url="props.apiUrl"
                            :csrf-token="props.csrfToken"
                            @save="handleBlockSave"
                            @delete="handleBlockDelete"
                        />
                    </div>
                </div>
            </div>

            <div v-if="blocks.length === 0" class="block-editor__empty">
                Немає блоків. Натисніть "Додати блок" щоб почати.
            </div>

            <!-- Add Block Button (Bottom) -->
            <div class="block-editor__add-button block-editor__add-button--bottom">
                <button type="button" @click="showSidebar = true" class="block-editor__add-button-btn">
                    <svg class="block-editor__add-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Додати блок
                </button>
                <button type="button" @click="pasteBlock" class="block-editor__paste-btn" :disabled="!clipboardBlock" :title="clipboardBlock ? 'Вставити скопійований блок' : 'Спочатку скопіюйте блок'">
                    <svg class="block-editor__paste-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Вставити блок
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, reactive, computed } from 'vue';
import BlockForm from './BlockForm.vue';

export default {
    name: 'BlockEditor',
    components: {
        BlockForm,
    },
    props: {
        modelId: {
            type: Number,
            required: true,
        },
        modelClass: {
            type: String,
            required: true,
        },
        contentField: {
            type: String,
            default: 'content',
        },
        locale: {
            type: String,
            default: 'uk',
        },
        apiUrl: {
            type: String,
            required: true,
        },
        csrfToken: {
            type: String,
            required: true,
        },
    },
    setup(props) {
        const showSidebar = ref(false);
        const blocks = ref([]);
        const openBlocks = reactive({});
        const availableBlocks = ref({});
        const groupedBlocks = ref({});
        const searchQuery = ref('');
        const draggedBlockIndex = ref(null);
        const visibilityUpdating = reactive({});
        const notification = ref({ show: false, message: '', type: 'success' });
        const fileInput = ref(null);
        const clipboardBlock = ref(null);
        const copyTooltip = reactive({
            visible: false,
            x: 0,
            y: 0,
        });
        const previewTooltip = reactive({
            visible: false,
            src: null,
            alt: '',
            x: 0,
            y: 0,
        });

        /**
         * Load available blocks from API.
         */
        const loadAvailableBlocks = async () => {
            try {
                const params = new URLSearchParams({
                    modelId: String(props.modelId ?? ''),
                    modelClass: props.modelClass ?? '',
                    locale: props.locale ?? 'uk',
                });
                const response = await fetch(`${props.apiUrl}/blocks/available?${params.toString()}`);
                const data = await response.json();
                availableBlocks.value = data.blocks || {};
                groupedBlocks.value = data.grouped || {};
            } catch (error) {
                console.error('Failed to load available blocks:', error);
            }
        };

        /**
         * Filter blocks by search query.
         */
        const filteredGroupedBlocks = computed(() => {
            if (!searchQuery.value.trim()) {
                return groupedBlocks.value;
            }

            const query = searchQuery.value.toLowerCase().trim();
            const filtered = {};

            for (const [category, blocksInCategory] of Object.entries(groupedBlocks.value)) {
                const filteredBlocks = {};

                for (const [type, config] of Object.entries(blocksInCategory)) {
                    const name = (config.name || '').toLowerCase();
                    const description = (config.description || '').toLowerCase();
                    const categoryName = category.toLowerCase();

                    if (
                        name.includes(query) ||
                        description.includes(query) ||
                        categoryName.includes(query) ||
                        type.toLowerCase().includes(query)
                    ) {
                        filteredBlocks[type] = config;
                    }
                }

                if (Object.keys(filteredBlocks).length > 0) {
                    filtered[category] = filteredBlocks;
                }
            }

            return filtered;
        });

        /**
         * Load blocks from API.
         */
        const loadBlocks = async () => {
            try {
                const response = await fetch(
                    `${props.apiUrl}/blocks?modelId=${props.modelId}&modelClass=${encodeURIComponent(props.modelClass)}&contentField=${props.contentField}&locale=${props.locale}`
                );
                const data = await response.json();
                blocks.value = data.blocks || [];
            } catch (error) {
                console.error('Failed to load blocks:', error);
            }
        };

        /**
         * Blocks created before the visibility switcher are enabled by default.
         */
        const isBlockEnabled = (block) => block?.settings?.enabled !== false;

        /**
         * Toggle frontend visibility without removing the block from the editor.
         */
        const toggleBlockVisibility = async (index) => {
            const block = blocks.value[index];

            if (!block || visibilityUpdating[index]) {
                return;
            }

            const previousBlock = JSON.parse(JSON.stringify(block));
            const enabled = !isBlockEnabled(block);
            const updatedBlock = {
                ...previousBlock,
                settings: {
                    ...(previousBlock.settings || {}),
                    enabled,
                },
            };

            visibilityUpdating[index] = true;
            blocks.value[index] = updatedBlock;

            try {
                const response = await fetch(`${props.apiUrl}/blocks/${index}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': props.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        modelId: props.modelId,
                        modelClass: props.modelClass,
                        contentField: props.contentField,
                        locale: props.locale,
                        blockData: updatedBlock,
                    }),
                });
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Не вдалося змінити видимість блоку');
                }

                showNotification(
                    enabled ? 'Блок увімкнено на сайті' : 'Блок вимкнено на сайті',
                    'success'
                );
            } catch (error) {
                blocks.value[index] = previousBlock;
                console.error('Failed to toggle block visibility:', error);
                showNotification('Не вдалося змінити видимість блоку', 'error');
            } finally {
                visibilityUpdating[index] = false;
            }
        };

        /**
         * Add a new block.
         */
        const addBlock = async (type) => {
            showSidebar.value = false;

            try {
                const response = await fetch(`${props.apiUrl}/blocks`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': props.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        modelId: props.modelId,
                        modelClass: props.modelClass,
                        contentField: props.contentField,
                        locale: props.locale,
                        type: type,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    await loadBlocks();
                    // Don't auto-open new blocks - keep them collapsed
                    showNotification('Блок успішно додано', 'success');
                } else {
                    showNotification(data.message || 'Помилка додавання блоку', 'error');
                    console.error('Failed to add block:', data.message);
                }
            } catch (error) {
                console.error('Failed to add block:', error);
            }
        };

        /**
         * Delete a block.
         */
        const deleteBlock = async (index) => {
            if (!confirm('Видалити цей блок?')) {
                return;
            }

            const block = blocks.value[index];
            if (!block) {
                return;
            }

            try {
                const response = await fetch(
                    `${props.apiUrl}/blocks/${index}`,
                    {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': props.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            modelId: props.modelId,
                            modelClass: props.modelClass,
                            contentField: props.contentField,
                            locale: props.locale,
                        }),
                    }
                );

                const data = await response.json();

                if (data.success) {
                    await loadBlocks();
                    delete openBlocks[index];
                    showNotification('Блок успішно видалено', 'success');
                } else {
                    showNotification(data.message || 'Помилка видалення блоку', 'error');
                    console.error('Failed to delete block:', data.message);
                }
            } catch (error) {
                console.error('Failed to delete block:', error);
            }
        };

        /**
         * Toggle block accordion.
         */
        const toggleBlock = (index) => {
            openBlocks[index] = !openBlocks[index];
        };

        /**
         * Show notification.
         */
        const showNotification = (message, type = 'success') => {
            notification.value = { show: true, message, type };
            setTimeout(() => {
                notification.value.show = false;
            }, 3000);
        };

        /**
         * Handle block save.
         */
        const handleBlockSave = async (blockData, blockIndex, showNotificationCallback) => {
            try {
                const response = await fetch(
                    `${props.apiUrl}/blocks/${blockIndex}`,
                    {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': props.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            modelId: props.modelId,
                            modelClass: props.modelClass,
                            contentField: props.contentField,
                            locale: props.locale,
                            blockData: blockData,
                        }),
                    }
                );

                const data = await response.json();

                if (data.success) {
                    await loadBlocks();
                    // Keep block open after save
                    openBlocks[blockIndex] = true;
                    if (showNotificationCallback && typeof showNotificationCallback === 'function') {
                        console.log('Calling showNotificationCallback from BlockForm');
                        showNotificationCallback('Блок успішно збережено', 'success');
                    } else {
                        console.log('showNotificationCallback not provided, using default');
                        showNotification('Блок успішно збережено', 'success');
                    }
                } else {
                    const errorMessage = data.message || 'Помилка збереження блоку';
                    if (showNotificationCallback && typeof showNotificationCallback === 'function') {
                        showNotificationCallback(errorMessage, 'error');
                    } else {
                        showNotification(errorMessage, 'error');
                    }
                    console.error('Failed to save block:', data.message);
                }
            } catch (error) {
                const errorMessage = 'Помилка збереження блоку';
                if (showNotificationCallback && typeof showNotificationCallback === 'function') {
                    showNotificationCallback(errorMessage, 'error');
                } else {
                    showNotification(errorMessage, 'error');
                }
                console.error('Failed to save block:', error);
            }
        };

        /**
         * Handle block delete.
         */
        const handleBlockDelete = (blockIndex) => {
            deleteBlock(blockIndex);
        };

        /**
         * Copy block to clipboard.
         */
        const copyBlock = (index, event) => {
            const block = blocks.value[index];
            if (!block) return;

            // Deep clone the block and save to localStorage for cross-page paste
            const copiedBlock = JSON.parse(JSON.stringify(block));
            localStorage.setItem('blockEditorClipboard', JSON.stringify(copiedBlock));
            clipboardBlock.value = copiedBlock;

            // Show tooltip near the button
            if (event) {
                const rect = event.target.getBoundingClientRect();
                copyTooltip.x = rect.left + rect.width / 2;
                copyTooltip.y = rect.top - 10;
                copyTooltip.visible = true;

                // Hide tooltip after 1.5 seconds
                setTimeout(() => {
                    copyTooltip.visible = false;
                }, 1500);
            }
        };

        /**
         * Paste block from clipboard.
         */
        const pasteBlock = async () => {
            if (!clipboardBlock.value) return;

            try {
                const response = await fetch(`${props.apiUrl}/blocks`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': props.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        modelId: props.modelId,
                        modelClass: props.modelClass,
                        contentField: props.contentField,
                        locale: props.locale,
                        type: clipboardBlock.value.type,
                        blockData: clipboardBlock.value,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    await loadBlocks();
                    showNotification('Блок успішно вставлено', 'success');
                } else {
                    showNotification(data.message || 'Помилка вставки блоку', 'error');
                }
            } catch (error) {
                console.error('Failed to paste block:', error);
                showNotification('Помилка вставки блоку', 'error');
            }
        };

        /**
         * Paste block after specified index.
         */
        const pasteBlockAfter = async (afterIndex) => {
            if (!clipboardBlock.value) return;

            try {
                const response = await fetch(`${props.apiUrl}/blocks/paste-after`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': props.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        modelId: props.modelId,
                        modelClass: props.modelClass,
                        contentField: props.contentField,
                        locale: props.locale,
                        type: clipboardBlock.value.type,
                        blockData: clipboardBlock.value,
                        afterIndex: afterIndex,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    await loadBlocks();
                    showNotification('Блок успішно вставлено', 'success');
                } else {
                    showNotification(data.message || 'Помилка вставки блоку', 'error');
                }
            } catch (error) {
                console.error('Failed to paste block after:', error);
                showNotification('Помилка вставки блоку', 'error');
            }
        };

        /**
         * Load clipboard from localStorage.
         */
        const loadClipboard = () => {
            try {
                const stored = localStorage.getItem('blockEditorClipboard');
                if (stored) {
                    clipboardBlock.value = JSON.parse(stored);
                }
            } catch (error) {
                console.error('Failed to load clipboard:', error);
            }
        };

        /**
         * Handle drag start.
         */
        const handleDragStart = (event, blockIndex) => {
            draggedBlockIndex.value = blockIndex;
            event.dataTransfer.effectAllowed = 'move';
            event.target.style.opacity = '0.5';
        };

        /**
         * Handle drag over.
         */
        const handleDragOver = (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        };

        /**
         * Handle drop.
         */
        const handleDrop = async (event, targetBlockIndex) => {
            event.preventDefault();

            if (
                draggedBlockIndex.value !== null &&
                draggedBlockIndex.value !== targetBlockIndex
            ) {
                const fromIndex = draggedBlockIndex.value;
                const toIndex = targetBlockIndex;

                try {
                    const response = await fetch(`${props.apiUrl}/blocks/reorder`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': props.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            modelId: props.modelId,
                            modelClass: props.modelClass,
                            contentField: props.contentField,
                            locale: props.locale,
                            fromIndex: fromIndex,
                            toIndex: toIndex,
                        }),
                    });

                    const data = await response.json();

                        if (data.success) {
                            await loadBlocks();
                            showNotification('Порядок блоків змінено', 'success');
                        } else {
                            showNotification(data.message || 'Помилка зміни порядку блоків', 'error');
                            console.error('Failed to reorder blocks:', data.message);
                        }
                } catch (error) {
                    console.error('Failed to reorder blocks:', error);
                }
            }

            handleDragEnd();
        };

        /**
         * Handle drag end.
         */
        const handleDragEnd = () => {
            draggedBlockIndex.value = null;
            document
                .querySelectorAll('[draggable="true"]')
                .forEach((el) => {
                    el.style.opacity = '1';
                });
        };

        /**
         * Truncate text.
         */
        const truncate = (text, length) => {
            if (!text) return '';
            return text.length > length ? text.substring(0, length) + '...' : text;
        };

        /**
         * Show preview tooltip with enlarged image.
         */
        const showPreviewTooltip = (event, src, alt) => {
            previewTooltip.src = src;
            previewTooltip.alt = alt || '';
            previewTooltip.visible = true;
            updateTooltipPosition(event);
        };

        /**
         * Hide preview tooltip.
         */
        const hidePreviewTooltip = () => {
            previewTooltip.visible = false;
            previewTooltip.src = null;
            previewTooltip.alt = '';
        };

        /**
         * Update tooltip position based on mouse position.
         */
        const updateTooltipPosition = (event) => {
            const tooltipWidth = 400;
            const tooltipHeight = 300;
            const offset = 15;

            let x = event.clientX + offset;
            let y = event.clientY + offset;

            // Adjust if tooltip goes off screen right
            if (x + tooltipWidth > window.innerWidth) {
                x = event.clientX - tooltipWidth - offset;
            }

            // Adjust if tooltip goes off screen bottom
            if (y + tooltipHeight > window.innerHeight) {
                y = event.clientY - tooltipHeight - offset;
            }

            previewTooltip.x = x;
            previewTooltip.y = y;
        };

        /**
         * Export blocks to JSON file.
         */
        const exportBlocks = () => {
            if (blocks.value.length === 0) {
                showNotification('Немає блоків для експорту', 'error');
                return;
            }

            const exportData = {
                version: '1.0',
                exportedAt: new Date().toISOString(),
                modelId: props.modelId,
                modelClass: props.modelClass,
                contentField: props.contentField,
                locale: props.locale,
                blocks: blocks.value,
            };

            const jsonString = JSON.stringify(exportData, null, 2);
            const blob = new Blob([jsonString], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `blocks-export-${props.modelId}-${Date.now()}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            showNotification('Блоки успішно експортовано', 'success');
        };

        /**
         * Trigger file input for import.
         */
        const triggerImport = () => {
            if (fileInput.value) {
                fileInput.value.click();
            }
        };

        /**
         * Import blocks from JSON file.
         */
        const importBlocks = async (event) => {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            if (!file.name.endsWith('.json')) {
                showNotification('Файл повинен бути у форматі JSON', 'error');
                return;
            }

            try {
                const text = await file.text();
                const importData = JSON.parse(text);

                // Validate import data structure
                if (!importData.blocks || !Array.isArray(importData.blocks)) {
                    showNotification('Невірний формат файлу. Очікується масив блоків.', 'error');
                    return;
                }

                // Confirm import
                if (!confirm(`Ви впевнені, що хочете імпортувати ${importData.blocks.length} блоків? Поточні блоки будуть замінені.`)) {
                    event.target.value = '';
                    return;
                }

                // Send import request to backend
                const response = await fetch(`${props.apiUrl}/blocks/import`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': props.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        modelId: props.modelId,
                        modelClass: props.modelClass,
                        contentField: props.contentField,
                        locale: props.locale,
                        blocks: importData.blocks,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    await loadBlocks();
                    showNotification(`Успішно імпортовано ${importData.blocks.length} блоків`, 'success');
                } else {
                    showNotification(data.message || 'Помилка імпорту блоків', 'error');
                    console.error('Failed to import blocks:', data.message);
                }
            } catch (error) {
                showNotification('Помилка читання файлу. Перевірте формат JSON.', 'error');
                console.error('Failed to import blocks:', error);
            } finally {
                // Reset file input
                event.target.value = '';
            }
        };

        /**
         * Initialize component.
         */
        onMounted(async () => {
            console.log('BlockEditor initialized', {
                modelId: props.modelId,
                modelClass: props.modelClass,
                locale: props.locale,
            });

            loadClipboard();
            await loadAvailableBlocks();
            await loadBlocks();
        });

            return {
                props,
                showSidebar,
                blocks,
                openBlocks,
                availableBlocks,
                groupedBlocks,
                searchQuery,
                filteredGroupedBlocks,
                notification,
                visibilityUpdating,
                fileInput,
                clipboardBlock,
                copyTooltip,
                previewTooltip,
                addBlock,
                isBlockEnabled,
                toggleBlockVisibility,
                deleteBlock,
                toggleBlock,
                handleBlockSave,
                handleBlockDelete,
                copyBlock,
                pasteBlock,
                pasteBlockAfter,
                handleDragStart,
                handleDragOver,
                handleDrop,
                handleDragEnd,
                truncate,
                showPreviewTooltip,
                hidePreviewTooltip,
                updateTooltipPosition,
                exportBlocks,
                triggerImport,
                importBlocks,
            };
    },
};
</script>

<style scoped>
[v-cloak] {
    display: none;
}
</style>
