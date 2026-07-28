<template>
    <div class="image-field">
        <div class="image-field__preview" @click="openSidebar">
            <div v-if="imageData.url" class="image-field__preview-content">
                <img :src="getImageUrl(imageData.url)" :alt="imageData.alt || 'Image'" class="image-field__preview-image" />
                <div class="image-field__preview-info">
                    <div class="image-field__preview-label">{{ label }}</div>
                    <div class="image-field__preview-url">{{ imageData.url }}</div>
                    <div v-if="imageData.width || imageData.height" class="image-field__preview-size">
                        {{ imageData.width }}x{{ imageData.height }}
                    </div>
                </div>
            </div>
            <div v-else class="image-field__preview-placeholder">
                <svg class="image-field__preview-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                <span>Налаштувати зображення</span>
            </div>
        </div>

        <!-- Sidebar Overlay -->
        <transition name="fade">
            <div v-if="showSidebar" class="image-field__overlay" @click="closeSidebar"></div>
        </transition>

        <!-- Sidebar -->
        <transition name="slide-right">
            <div v-if="showSidebar" class="image-field__sidebar">
                <div class="image-field__sidebar-header">
                    <h3 class="image-field__sidebar-title">{{ sidebarTitle }}</h3>
                    <button type="button" @click="closeSidebar" class="image-field__sidebar-close">
                        <svg class="image-field__sidebar-close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="image-field__sidebar-content">
                    <!-- Image Preview -->
                    <div v-if="imageData.url" class="image-field__sidebar-preview">
                        <div class="image-field__sidebar-preview-wrapper">
                            <img :src="getImageUrl(imageData.url)" :alt="imageData.alt || 'Прев\'ю'" class="image-field__sidebar-preview-image" />
                            <button type="button" @click="deleteImage" class="image-field__sidebar-preview-delete" title="Видалити зображення">
                                <svg class="image-field__sidebar-preview-delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                        <div v-if="imageData.alt" class="image-field__sidebar-preview-alt">
                            {{ imageData.alt }}
                        </div>
                    </div>

                    <div class="image-field__sidebar-field">
                        <label class="image-field__sidebar-label">
                            Завантажити зображення
                            <span v-if="required" class="image-field__required">*</span>
                        </label>
                        <div class="image-field__upload-wrapper">
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                @change="handleFileSelect"
                                class="image-field__file-input"
                            />
                            <button
                                type="button"
                                @click="triggerFileInput"
                                class="image-field__upload-btn"
                                :disabled="uploading"
                            >
                                <svg v-if="!uploading" class="image-field__upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <span v-if="uploading" class="image-field__upload-spinner"></span>
                                <span>{{ uploading ? 'Завантаження...' : 'Вибрати файл' }}</span>
                            </button>
                        </div>
                        <div v-if="uploadError" class="image-field__upload-error">
                            {{ uploadError }}
                        </div>
                    </div>

                    <div class="image-field__sidebar-divider">або</div>

                    <div class="image-field__sidebar-field">
                        <label class="image-field__sidebar-label">
                            URL зображення
                            <span v-if="required" class="image-field__required">*</span>
                        </label>
                        <input
                            v-model="imageData.url"
                            type="text"
                            :required="required"
                            placeholder="/images/example.jpg"
                            class="image-field__sidebar-input"
                        />
                    </div>

                    <div class="image-field__sidebar-field">
                        <label class="image-field__sidebar-label">Alt текст</label>
                        <input
                            v-model="imageData.alt"
                            type="text"
                            placeholder="Опис зображення"
                            class="image-field__sidebar-input"
                        />
                    </div>

                    <div class="image-field__sidebar-row">
                        <div class="image-field__sidebar-field">
                            <label class="image-field__sidebar-label">Ширина (px)</label>
                            <input
                                v-model.number="imageData.width"
                                type="number"
                                placeholder="Авто"
                                min="0"
                                class="image-field__sidebar-input"
                            />
                        </div>

                        <div class="image-field__sidebar-field">
                            <label class="image-field__sidebar-label">Висота (px)</label>
                            <input
                                v-model.number="imageData.height"
                                type="number"
                                placeholder="Авто"
                                min="0"
                                class="image-field__sidebar-input"
                            />
                        </div>
                    </div>

                    <div class="image-field__sidebar-field">
                        <label class="image-field__sidebar-label">CSS клас</label>
                        <input
                            v-model="imageData.class"
                            type="text"
                            placeholder="Додаткові CSS класи"
                            class="image-field__sidebar-input"
                        />
                    </div>

                    <div class="image-field__sidebar-field">
                        <label class="image-field__sidebar-label">
                            <input
                                v-model="imageData.lazy"
                                type="checkbox"
                                class="image-field__sidebar-checkbox"
                            />
                            <span>Відкладене завантаження</span>
                        </label>
                    </div>
                </div>

                <div class="image-field__sidebar-actions">
                    <button type="button" @click="saveImage" class="image-field__sidebar-btn image-field__sidebar-btn--primary">
                        Зберегти
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { ref, reactive, watch } from 'vue';

export default {
    name: 'ImageField',
    props: {
        modelValue: {
            type: Object,
            default: () => ({}),
        },
        label: {
            type: String,
            required: true,
        },
        required: {
            type: Boolean,
            default: false,
        },
        sidebarTitle: {
            type: String,
            default: 'Налаштування зображення',
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
        const showSidebar = ref(false);
        const fileInput = ref(null);
        const uploading = ref(false);
        const uploadError = ref('');
        const oldImagePath = ref(null);
        const imageData = reactive({
            url: (props.modelValue && props.modelValue.url) ? props.modelValue.url : '',
            alt: (props.modelValue && props.modelValue.alt) ? props.modelValue.alt : '',
            width: (props.modelValue && props.modelValue.width) ? props.modelValue.width : null,
            height: (props.modelValue && props.modelValue.height) ? props.modelValue.height : null,
            class: (props.modelValue && props.modelValue.class) ? props.modelValue.class : '',
            lazy: (props.modelValue && props.modelValue.lazy) ? props.modelValue.lazy : false,
        });

        /**
         * Watch for external changes.
         */
        watch(() => props.modelValue, (newValue) => {
            if (newValue && typeof newValue === 'object') {
                // Store old path before updating
                if (imageData.url && imageData.url !== (newValue.url || '')) {
                    oldImagePath.value = extractStoragePath(imageData.url);
                }
                imageData.url = newValue.url || '';
                imageData.alt = newValue.alt || '';
                imageData.width = newValue.width || null;
                imageData.height = newValue.height || null;
                imageData.class = newValue.class || '';
                imageData.lazy = newValue.lazy || false;
            } else {
                imageData.url = '';
                imageData.alt = '';
                imageData.width = null;
                imageData.height = null;
                imageData.class = '';
                imageData.lazy = false;
                oldImagePath.value = null;
            }
        }, { deep: true, immediate: true });

        /**
         * Open sidebar.
         */
        const openSidebar = () => {
            showSidebar.value = true;
        };

        /**
         * Close sidebar.
         */
        const closeSidebar = () => {
            showSidebar.value = false;
        };

        /**
         * Save image data.
         */
        const saveImage = () => {
            emit('update:modelValue', { ...imageData });
            closeSidebar();
        };

        /**
         * Delete image.
         */
        const deleteImage = async () => {
            // Delete old image from storage if exists
            const currentPath = extractStoragePath(imageData.url);
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
                    console.error('Failed to delete image:', error);
                }
            }

            imageData.url = '';
            imageData.alt = '';
            imageData.width = null;
            imageData.height = null;
            imageData.class = '';
            imageData.lazy = false;
            oldImagePath.value = null;
            emit('update:modelValue', {});
        };

        /**
         * Trigger file input click.
         */
        const triggerFileInput = () => {
            if (fileInput.value) {
                fileInput.value.click();
            }
        };

        /**
         * Extract storage path from URL.
         */
        const extractStoragePath = (url) => {
            if (!url) {
                return null;
            }
            // Extract path from /storage/images/blocks/filename.jpg
            if (url.includes('/storage/')) {
                return url.replace(/^.*\/storage\//, '');
            }
            // If it's already a storage path
            if (url.startsWith('images/blocks/')) {
                return url;
            }
            return null;
        };

        /**
         * Handle file selection.
         */
        const handleFileSelect = async (event) => {
            const file = event.target.files[0];
            if (!file) {
                return;
            }

            uploading.value = true;
            uploadError.value = '';

            try {
                // Get current image path before uploading new one
                const currentPath = extractStoragePath(imageData.url);

                const formData = new FormData();
                formData.append('image', file);
                if (currentPath) {
                    formData.append('oldPath', currentPath);
                }

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
                    // Clear old path since new image is uploaded
                    oldImagePath.value = null;
                    imageData.url = data.url;
                    uploadError.value = '';
                } else {
                    uploadError.value = data.message || 'Помилка завантаження зображення';
                }
            } catch (error) {
                uploadError.value = 'Помилка завантаження зображення: ' + error.message;
                console.error('Failed to upload image:', error);
            } finally {
                uploading.value = false;
                // Reset file input
                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            }
        };

        /**
         * Get image URL (handle both relative and absolute URLs).
         */
        const getImageUrl = (url) => {
            if (!url) {
                return '';
            }
            // If URL starts with http:// or https://, return as is
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return url;
            }
            // If URL starts with /, return as is (already relative)
            if (url.startsWith('/')) {
                return url;
            }
            // Otherwise, prepend / to make it relative
            return '/' + url;
        };

        return {
            showSidebar,
            imageData,
            fileInput,
            uploading,
            uploadError,
            oldImagePath,
            openSidebar,
            closeSidebar,
            saveImage,
            deleteImage,
            triggerFileInput,
            handleFileSelect,
            getImageUrl,
            extractStoragePath,
        };
    },
};
</script>

<style scoped>
/* Styles are in block-editor.css */
</style>

