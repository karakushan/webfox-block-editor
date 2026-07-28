<template>
    <div class="repeater-field">
        <div class="repeater-field__header">
            <button
                v-if="items.length > 0"
                type="button"
                @click="toggleAllItems"
                class="repeater-field__toggle-all-btn"
                :data-collapsed="allItemsCollapsed"
            >
                <svg class="repeater-field__toggle-all-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                <span>{{ allItemsCollapsed ? 'Розгорнути всі' : 'Згорнути всі' }}</span>
            </button>
        </div>

        <div class="repeater-field__items">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="repeater-field__item"
            >
                <div class="repeater-field__item-header" @click="toggleItem(index)">
                    <div class="repeater-field__item-header-left">
                        <div class="repeater-field__item-arrow" :class="{ 'repeater-field__item-arrow--open': openItems[index] }">
                            <svg class="repeater-field__item-arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        <span class="repeater-field__item-number">{{ index + 1 }}</span>
                        <span v-if="getFirstTextFieldValue(item)" class="repeater-field__item-preview">
                            {{ getFirstTextFieldValue(item) }}
                        </span>
                    </div>
                    <div class="repeater-field__item-header-actions" @click.stop>
                        <button
                            type="button"
                            @click="copyItem(index)"
                            class="repeater-field__item-copy"
                            title="Копіювати елемент"
                        >
                            <svg class="repeater-field__item-copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="removeItem(index)"
                            class="repeater-field__item-remove"
                            title="Видалити елемент"
                        >
                            <svg class="repeater-field__item-remove-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-show="openItems[index]" class="repeater-field__item-fields">
                    <div
                        v-for="(fieldConfig, fieldName) in fields"
                        :key="fieldName"
                        class="repeater-field__item-field"
                    >
                        <label class="repeater-field__item-label">
                            {{ fieldConfig.label }}
                            <span v-if="fieldConfig.required" class="repeater-field__required">*</span>
                        </label>

                        <!-- Text input -->
                        <input
                            v-if="fieldConfig.type === 'text'"
                            :value="item[fieldName]"
                            @input="updateField(index, fieldName, $event.target.value)"
                            type="text"
                            :placeholder="fieldConfig.placeholder || ''"
                            class="repeater-field__input"
                        />

                        <!-- Textarea -->
                        <textarea
                            v-else-if="fieldConfig.type === 'textarea'"
                            :value="item[fieldName]"
                            @input="updateField(index, fieldName, $event.target.value)"
                            :placeholder="fieldConfig.placeholder || ''"
                            :rows="fieldConfig.rows || 3"
                            class="repeater-field__textarea"
                        ></textarea>

                        <!-- Number input -->
                        <input
                            v-else-if="fieldConfig.type === 'number'"
                            :value="item[fieldName]"
                            @input="updateField(index, fieldName, parseFloat($event.target.value) || null)"
                            type="number"
                            :placeholder="fieldConfig.placeholder || ''"
                            :min="fieldConfig.min"
                            :max="fieldConfig.max"
                            class="repeater-field__input"
                        />

                        <!-- Button field -->
                        <button-field
                            v-else-if="fieldConfig.type === 'button'"
                            :model-value="getButtonFieldValue(item[fieldName])"
                            @update:model-value="updateButtonField(index, fieldName, $event)"
                            :label="fieldConfig.label"
                            :required="fieldConfig.required"
                            :sidebar-title="fieldConfig.sidebarTitle || 'Налаштування кнопки'"
                            class="repeater-field__button-field"
                        />

                        <!-- Image field -->
                        <image-field
                            v-else-if="fieldConfig.type === 'image'"
                            :model-value="getImageFieldValue(item[fieldName])"
                            @update:model-value="updateImageField(index, fieldName, $event)"
                            :label="fieldConfig.label"
                            :required="fieldConfig.required"
                            :sidebar-title="fieldConfig.sidebarTitle || 'Налаштування зображення'"
                            :api-url="apiUrl"
                            :csrf-token="csrfToken"
                            class="repeater-field__image-field"
                        />

                        <!-- File upload field -->
                        <div v-else-if="fieldConfig.type === 'file' || fieldConfig.type === 'icon'" class="repeater-field__file-wrapper">
                            <div v-if="item[fieldName]" class="repeater-field__file-preview">
                                <img v-if="isImageFile(item[fieldName])" :src="getFileUrl(item[fieldName])" :alt="fieldConfig.label" class="repeater-field__file-preview-image" />
                                <div v-else class="repeater-field__file-preview-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="repeater-field__file-preview-info">
                                    <div class="repeater-field__file-preview-name">{{ getFileName(item[fieldName]) }}</div>
                                    <button
                                        type="button"
                                        @click="deleteFile(index, fieldName)"
                                        class="repeater-field__file-preview-delete"
                                        title="Видалити файл"
                                    >
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="repeater-field__file-upload">
                                <input
                                    :ref="el => setFileInputRef(index, fieldName, el)"
                                    type="file"
                                    :accept="fieldConfig.accept || 'image/*'"
                                    @change="handleFileSelect(index, fieldName, $event)"
                                    class="repeater-field__file-input"
                                />
                                <button
                                    type="button"
                                    @click="triggerFileInput(index, fieldName)"
                                    class="repeater-field__file-btn"
                                    :disabled="uploadingStates[`${index}-${fieldName}`]"
                                >
                                    <svg v-if="!uploadingStates[`${index}-${fieldName}`]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <span v-if="uploadingStates[`${index}-${fieldName}`]" class="repeater-field__file-spinner"></span>
                                    <span>{{ uploadingStates[`${index}-${fieldName}`] ? 'Завантаження...' : (item[fieldName] ? 'Замінити файл' : 'Вибрати файл') }}</span>
                                </button>
                                <div v-if="uploadErrors[`${index}-${fieldName}`]" class="repeater-field__file-error">
                                    {{ uploadErrors[`${index}-${fieldName}`] }}
                                </div>
                            </div>
                        </div>

                        <!-- URL input -->
                        <input
                            v-else-if="fieldConfig.type === 'url'"
                            :value="item[fieldName]"
                            @input="updateField(index, fieldName, $event.target.value)"
                            type="text"
                            :placeholder="fieldConfig.placeholder || ''"
                            class="repeater-field__input"
                        />

                        <!-- Email input -->
                        <input
                            v-else-if="fieldConfig.type === 'email'"
                            :value="item[fieldName]"
                            @input="updateField(index, fieldName, $event.target.value)"
                            type="email"
                            :placeholder="fieldConfig.placeholder || ''"
                            class="repeater-field__input"
                        />

                        <!-- Select -->
                        <select
                            v-else-if="fieldConfig.type === 'select'"
                            :value="item[fieldName]"
                            @change="updateField(index, fieldName, $event.target.value)"
                            class="repeater-field__select"
                        >
                            <option value="">{{ fieldConfig.placeholder || 'Виберіть опцію' }}</option>
                            <option
                                v-for="option in fieldConfig.options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>

                        <!-- Color picker -->
                        <input
                            v-else-if="fieldConfig.type === 'color'"
                            :value="item[fieldName]"
                            @input="updateField(index, fieldName, $event.target.value)"
                            type="color"
                            class="repeater-field__input repeater-field__input--color"
                        />

                        <!-- Nested Repeater Field -->
                        <component
                            v-else-if="fieldConfig.type === 'repeater'"
                            :is="RepeaterFieldComponent"
                            :model-value="getRepeaterFieldValue(item[fieldName])"
                            @update:model-value="updateRepeaterField(index, fieldName, $event)"
                            :label="fieldConfig.label"
                            :required="fieldConfig.required"
                            :fields="fieldConfig.fields || {}"
                            :default-item="fieldConfig.defaultItem || {}"
                            :api-url="apiUrl"
                            :csrf-token="csrfToken"
                            class="repeater-field__nested"
                        />
                    </div>
                </div>
            </div>
        </div>

        <button
            type="button"
            @click="addItem"
            class="repeater-field__add-btn"
        >
            <svg class="repeater-field__add-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Додати елемент</span>
        </button>
    </div>
</template>

<script>
import { ref, reactive, watch, resolveComponent } from 'vue';
import ImageField from './ImageField.vue';
import ButtonField from './ButtonField.vue';

export default {
    name: 'RepeaterField',
    components: {
        ImageField,
        ButtonField,
    },
    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },
        label: {
            type: String,
            required: true,
        },
        required: {
            type: Boolean,
            default: false,
        },
        fields: {
            type: Object,
            required: true,
        },
        defaultItem: {
            type: Object,
            default: () => ({}),
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
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const items = ref([]);
        const openItems = reactive({});
        const allItemsCollapsed = ref(true);
        const fileInputRefs = ref({});
        const uploadingStates = ref({});
        const uploadErrors = ref({});
        const oldFilePaths = ref({});

        /**
         * Initialize items from modelValue.
         */
        const initializeItems = () => {
            if (Array.isArray(props.modelValue) && props.modelValue.length > 0) {
                items.value = props.modelValue.map(item => ({ ...item }));
            } else {
                items.value = [];
            }
            // Reset openItems - all items collapsed by default
            Object.keys(openItems).forEach(key => {
                delete openItems[key];
            });
            updateAllItemsCollapsedState();
        };

        /**
         * Create a new empty item based on defaultItem and fields.
         */
        const createEmptyItem = () => {
            const item = {};
            if (props.defaultItem && typeof props.defaultItem === 'object') {
                Object.assign(item, props.defaultItem);
            }
            // Initialize fields from fields config
            if (props.fields) {
                Object.keys(props.fields).forEach((fieldName) => {
                    const fieldConfig = props.fields[fieldName];
                    if (!(fieldName in item)) {
                        if (fieldConfig.type === 'number') {
                            item[fieldName] = fieldConfig.default ?? null;
                        } else if (fieldConfig.type === 'select') {
                            item[fieldName] = fieldConfig.default ?? '';
                        } else {
                            item[fieldName] = fieldConfig.default ?? '';
                        }
                    }
                });
            }
            return item;
        };

        /**
         * Toggle item accordion.
         */
        const toggleItem = (index) => {
            openItems[index] = !openItems[index];
            updateAllItemsCollapsedState();
        };

        /**
         * Toggle all items (collapse/expand all).
         */
        const toggleAllItems = () => {
            const shouldCollapse = !allItemsCollapsed.value;
            items.value.forEach((_, index) => {
                if (shouldCollapse) {
                    delete openItems[index];
                } else {
                    openItems[index] = true;
                }
            });
            allItemsCollapsed.value = shouldCollapse;
        };

        /**
         * Update all items collapsed state.
         */
        const updateAllItemsCollapsedState = () => {
            if (items.value.length === 0) {
                allItemsCollapsed.value = true;
                return;
            }
            const openCount = items.value.filter((_, index) => openItems[index]).length;
            allItemsCollapsed.value = openCount === 0;
        };

        /**
         * Copy an item (duplicate).
         */
        const copyItem = (index) => {
            const item = items.value[index];
            if (!item) {
                return;
            }
            // Create a deep copy of the item
            const itemCopy = JSON.parse(JSON.stringify(item));
            // Insert right after the original item
            items.value.splice(index + 1, 0, itemCopy);
            // Keep the copied item collapsed
            openItems[index + 1] = false;
            updateAllItemsCollapsedState();
            emitUpdate();
        };

        /**
         * Add a new item.
         */
        const addItem = () => {
            items.value.push(createEmptyItem());
            // New items are collapsed by default
            const newIndex = items.value.length - 1;
            openItems[newIndex] = false;
            updateAllItemsCollapsedState();
            emitUpdate();
        };

        /**
         * Remove an item by index.
         */
        const removeItem = (index) => {
            if (items.value.length > index) {
                items.value.splice(index, 1);
                delete openItems[index];
                // Reindex openItems
                const newOpenItems = {};
                Object.keys(openItems).forEach(key => {
                    const keyNum = parseInt(key);
                    if (keyNum < index) {
                        newOpenItems[keyNum] = openItems[keyNum];
                    } else if (keyNum > index) {
                        newOpenItems[keyNum - 1] = openItems[keyNum];
                    }
                });
                Object.keys(openItems).forEach(key => delete openItems[key]);
                Object.keys(newOpenItems).forEach(key => {
                    openItems[key] = newOpenItems[key];
                });
                updateAllItemsCollapsedState();
                emitUpdate();
            }
        };

        /**
         * Update a field value.
         */
        const updateField = (itemIndex, fieldName, value) => {
            if (items.value[itemIndex]) {
                items.value[itemIndex][fieldName] = value;
                emitUpdate();
            }
        };

        /**
         * Get button field value (convert string URL to object if needed).
         */
        const getButtonFieldValue = (value) => {
            if (!value) {
                return {
                    text: '',
                    url: '',
                    openInNewTab: false,
                };
            }
            if (typeof value === 'string') {
                return {
                    text: '',
                    url: value,
                    openInNewTab: false,
                };
            }
            return value;
        };

        /**
         * Update button field value.
         */
        const updateButtonField = (itemIndex, fieldName, value) => {
            if (items.value[itemIndex]) {
                items.value[itemIndex][fieldName] = value || {
                    text: '',
                    url: '',
                    openInNewTab: false,
                };
                emitUpdate();
            }
        };

        /**
         * Get image field value (convert string URL to object if needed).
         */
        const getImageFieldValue = (value) => {
            if (!value) {
                return {
                    url: '',
                    alt: '',
                    width: null,
                    height: null,
                    class: '',
                    lazy: false,
                };
            }
            if (typeof value === 'string') {
                return {
                    url: value,
                    alt: '',
                    width: null,
                    height: null,
                    class: '',
                    lazy: false,
                };
            }
            return value;
        };

        /**
         * Update image field value.
         */
        const updateImageField = (itemIndex, fieldName, value) => {
            if (items.value[itemIndex]) {
                // If it's an object with url, store the whole object, otherwise store just the url
                if (value && typeof value === 'object' && value.url) {
                    items.value[itemIndex][fieldName] = value;
                } else if (value && typeof value === 'object') {
                    items.value[itemIndex][fieldName] = value.url || '';
                } else {
                    items.value[itemIndex][fieldName] = value || '';
                }
                emitUpdate();
            }
        };

        /**
         * Emit update event.
         */
        const emitUpdate = () => {
            emit('update:modelValue', items.value.map(item => ({ ...item })));
        };

        /**
         * Watch for external changes.
         */
        watch(() => props.modelValue, (newValue) => {
            if (Array.isArray(newValue)) {
                const newItems = newValue.map(item => ({ ...item }));
                const currentItemsStr = JSON.stringify(items.value);
                const newItemsStr = JSON.stringify(newItems);
                // Only update if values are actually different
                if (currentItemsStr !== newItemsStr) {
                    items.value = newItems;
                    // Reset openItems - all items collapsed by default
                    Object.keys(openItems).forEach(key => {
                        delete openItems[key];
                    });
                    updateAllItemsCollapsedState();
                }
            } else {
                if (items.value.length > 0) {
                    items.value = [];
                }
                Object.keys(openItems).forEach(key => {
                    delete openItems[key];
                });
                updateAllItemsCollapsedState();
            }
        }, { deep: true, immediate: true });

        /**
         * Set file input ref.
         */
        const setFileInputRef = (itemIndex, fieldName, el) => {
            if (el) {
                fileInputRefs.value[`${itemIndex}-${fieldName}`] = el;
            }
        };

        /**
         * Trigger file input click.
         */
        const triggerFileInput = (itemIndex, fieldName) => {
            const key = `${itemIndex}-${fieldName}`;
            if (fileInputRefs.value[key]) {
                fileInputRefs.value[key].click();
            }
        };

        /**
         * Handle file selection and upload.
         */
        const handleFileSelect = async (itemIndex, fieldName, event) => {
            const file = event.target.files[0];
            if (!file) {
                return;
            }

            const key = `${itemIndex}-${fieldName}`;
            uploadingStates.value[key] = true;
            uploadErrors.value[key] = '';

            const formData = new FormData();
            formData.append('image', file);
            
            // Get old path for deletion
            const oldPath = oldFilePaths.value[key];
            if (oldPath) {
                formData.append('oldPath', oldPath);
            }

            try {
                const response = await fetch(`${props.apiUrl}/images/upload`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': props.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    updateField(itemIndex, fieldName, data.url);
                    oldFilePaths.value[key] = data.path;
                    uploadErrors.value[key] = '';
                } else {
                    uploadErrors.value[key] = data.message || 'Помилка завантаження файлу';
                }
            } catch (error) {
                uploadErrors.value[key] = 'Помилка завантаження файлу: ' + error.message;
                console.error('Failed to upload file:', error);
            } finally {
                uploadingStates.value[key] = false;
                // Reset file input
                if (fileInputRefs.value[key]) {
                    fileInputRefs.value[key].value = '';
                }
            }
        };

        /**
         * Delete file.
         */
        const deleteFile = async (itemIndex, fieldName) => {
            const key = `${itemIndex}-${fieldName}`;
            const currentPath = extractStoragePath(items.value[itemIndex]?.[fieldName]);
            
            if (currentPath) {
                try {
                    await fetch(`${props.apiUrl}/images/delete`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': props.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ path: currentPath }),
                    });
                } catch (error) {
                    console.error('Failed to delete file:', error);
                }
            }

            updateField(itemIndex, fieldName, '');
            oldFilePaths.value[key] = null;
        };

        /**
         * Check if file is an image.
         */
        const isImageFile = (url) => {
            if (!url) return false;
            const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
            return imageExtensions.some(ext => url.toLowerCase().includes(ext));
        };

        /**
         * Get file URL for display.
         */
        const getFileUrl = (url) => {
            if (!url) return '';
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return url;
            }
            if (url.startsWith('/storage/')) {
                return url;
            }
            return '/' + url;
        };

        /**
         * Get file name from URL.
         */
        const getFileName = (url) => {
            if (!url) return '';
            const parts = url.split('/');
            return parts[parts.length - 1];
        };

        /**
         * Extract storage path from URL.
         */
        const extractStoragePath = (url) => {
            if (!url) return null;
            if (url.startsWith('/storage/')) {
                return url.replace(/^\/storage\//, '');
            }
            if (url.startsWith('images/blocks/')) {
                return url;
            }
            return null;
        };

        /**
         * Get repeater field value (ensure it's an array).
         */
        const getRepeaterFieldValue = (value) => {
            if (!value) {
                return [];
            }
            if (Array.isArray(value)) {
                return value;
            }
            return [];
        };

        /**
         * Update nested repeater field value.
         */
        const updateRepeaterField = (itemIndex, fieldName, value) => {
            if (items.value[itemIndex]) {
                items.value[itemIndex][fieldName] = Array.isArray(value) ? value : [];
                emitUpdate();
            }
        };

        /**
         * Get first text field value from item for preview.
         */
        const getFirstTextFieldValue = (item) => {
            if (!item || !props.fields) {
                return '';
            }

            // Find first text field in fields config
            const firstTextFieldName = Object.keys(props.fields).find(fieldName => {
                const fieldConfig = props.fields[fieldName];
                return fieldConfig && fieldConfig.type === 'text';
            });

            if (!firstTextFieldName) {
                return '';
            }

            const value = item[firstTextFieldName];
            if (!value || typeof value !== 'string') {
                return '';
            }

            // Truncate to 50 characters
            return value.length > 50 ? value.substring(0, 50) + '...' : value;
        };

        // Initialize on mount
        initializeItems();

        // Resolve RepeaterField component for recursive use
        const RepeaterFieldComponent = resolveComponent('RepeaterField');

        return {
            items,
            openItems,
            allItemsCollapsed,
            toggleItem,
            toggleAllItems,
            copyItem,
            addItem,
            removeItem,
            updateField,
            getButtonFieldValue,
            updateButtonField,
            getImageFieldValue,
            updateImageField,
            setFileInputRef,
            triggerFileInput,
            handleFileSelect,
            deleteFile,
            isImageFile,
            getFileUrl,
            getFileName,
            uploadingStates,
            uploadErrors,
            getRepeaterFieldValue,
            updateRepeaterField,
            getFirstTextFieldValue,
            RepeaterFieldComponent,
            apiUrl: props.apiUrl,
            csrfToken: props.csrfToken,
        };
    },
};
</script>

<style scoped>
/* Styles are in block-editor.css */
</style>

