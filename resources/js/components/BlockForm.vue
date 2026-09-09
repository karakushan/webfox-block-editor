<template>
    <div class="block-form">
        <form @submit.prevent="save" novalidate>
            <div v-if="blockConfig" class="block-form__fields">
                <!-- Data Fields -->
                <div v-if="dataFields && Object.keys(dataFields).length > 0" class="block-form__section">
                    <h4 class="block-form__section-title">Дані блоку</h4>
                    <div class="block-form__section-fields">
                        <div
                            v-for="(fieldConfig, fieldName) in dataFields"
                            :key="'data-' + fieldName"
                            class="block-form__field"
                        >
                            <label class="block-form__label">
                                {{ fieldConfig.label }}
                                <span v-if="fieldConfig.required" class="block-form__required">*</span>
                            </label>
                            <input
                                v-if="fieldConfig.type === 'text'"
                                v-model="formData.data[fieldName]"
                                type="text"
                                :placeholder="fieldConfig.placeholder || ''"
                                class="block-form__input"
                            />
                            <textarea
                                v-else-if="fieldConfig.type === 'textarea'"
                                v-model="formData.data[fieldName]"
                                :placeholder="fieldConfig.placeholder || ''"
                                :rows="fieldConfig.rows || 5"
                                class="block-form__textarea"
                            ></textarea>
                            <wysiwyg-field
                                v-else-if="fieldConfig.type === 'wysiwyg'"
                                :ref="el => setWysiwygRef(el, `data-${fieldName}`)"
                                v-model="formData.data[fieldName]"
                                :placeholder="fieldConfig.placeholder || ''"
                                :height="fieldConfig.height || 400"
                                :api-url="props.apiUrl"
                                :csrf-token="props.csrfToken"
                                :key="`wysiwyg-data-${fieldName}-${blockIndex}`"
                                class="block-form__wysiwyg"
                            />
                            <input
                                v-else-if="fieldConfig.type === 'number'"
                                v-model.number="formData.data[fieldName]"
                                type="number"
                                :placeholder="fieldConfig.placeholder || ''"
                                :min="fieldConfig.min"
                                :max="fieldConfig.max"
                                class="block-form__input"
                            />
                            <input
                                v-else-if="fieldConfig.type === 'url'"
                                v-model="formData.data[fieldName]"
                                type="text"
                                :placeholder="fieldConfig.placeholder || ''"
                                class="block-form__input"
                            />
                            <input
                                v-else-if="fieldConfig.type === 'email'"
                                v-model="formData.data[fieldName]"
                                type="text"
                                :placeholder="fieldConfig.placeholder || ''"
                                class="block-form__input"
                            />
                            <select-field
                                v-else-if="fieldConfig.type === 'select'"
                                v-model="formData.data[fieldName]"
                                :options="fieldConfig.options || []"
                                :placeholder="fieldConfig.placeholder || 'Виберіть опцію'"
                                :multiple="fieldConfig.multiple === true"
                                :searchable="fieldConfig.searchable === true"
                            />
                            <button-field
                                v-else-if="fieldConfig.type === 'button'"
                                v-model="formData.data[fieldName]"
                                :label="fieldConfig.label"
                                :required="fieldConfig.required"
                                :sidebar-title="fieldConfig.sidebarTitle || 'Налаштування кнопки'"
                                class="block-form__button-field"
                            />
                            <image-field
                                v-else-if="fieldConfig.type === 'image'"
                                v-model="formData.data[fieldName]"
                                :label="fieldConfig.label"
                                :required="fieldConfig.required"
                                :sidebar-title="fieldConfig.sidebarTitle || 'Налаштування зображення'"
                                :api-url="props.apiUrl"
                                :csrf-token="props.csrfToken"
                                class="block-form__image-field"
                            />
                            <repeater-field
                                v-else-if="fieldConfig.type === 'repeater'"
                                v-model="formData.data[fieldName]"
                                :label="fieldConfig.label"
                                :required="fieldConfig.required"
                                :fields="fieldConfig.fields || {}"
                                :default-item="fieldConfig.defaultItem || {}"
                                :api-url="props.apiUrl"
                                :csrf-token="props.csrfToken"
                                class="block-form__repeater-field"
                            />
                            <div v-if="fieldConfig.help" class="block-form__help">
                                {{ fieldConfig.help }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Fields -->
                <div v-if="settingsFields && Object.keys(settingsFields).length > 0" class="block-form__section">
                    <button
                        type="button"
                        class="block-form__section-title block-form__section-toggle"
                        :aria-expanded="settingsExpanded"
                        @click="settingsExpanded = !settingsExpanded"
                    >
                        <span class="block-form__section-toggle-label">
                            <img
                                v-if="blockConfig.settings_icon"
                                :src="blockConfig.settings_icon"
                                alt=""
                                aria-hidden="true"
                                class="block-form__section-toggle-icon-image"
                            />
                            <span>{{ blockConfig.settings_title || 'Налаштування' }}</span>
                        </span>
                        <svg
                            aria-hidden="true"
                            class="block-form__section-toggle-icon"
                            :class="{ 'block-form__section-toggle-icon--expanded': settingsExpanded }"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div v-show="settingsExpanded" class="block-form__section-fields">
                        <div
                            v-for="(fieldConfig, fieldName) in settingsFields"
                            :key="'settings-' + fieldName"
                            class="block-form__field"
                        >
                            <label class="block-form__label">
                                {{ fieldConfig.label }}
                                <span v-if="fieldConfig.required" class="block-form__required">*</span>
                            </label>
                            <input
                                v-if="fieldConfig.type === 'text'"
                                v-model="formData.settings[fieldName]"
                                type="text"
                                :placeholder="fieldConfig.placeholder || ''"
                                class="block-form__input"
                            />
                            <input
                                v-else-if="fieldConfig.type === 'color'"
                                v-model="formData.settings[fieldName]"
                                type="color"
                                class="block-form__input block-form__input--color"
                            />
                            <input
                                v-else-if="fieldConfig.type === 'number'"
                                v-model.number="formData.settings[fieldName]"
                                type="number"
                                :placeholder="fieldConfig.placeholder || ''"
                                :min="fieldConfig.min"
                                :max="fieldConfig.max"
                                class="block-form__input"
                            />
                            <select-field
                                v-else-if="fieldConfig.type === 'select'"
                                v-model="formData.settings[fieldName]"
                                :options="fieldConfig.options || []"
                                :placeholder="fieldConfig.placeholder || 'Виберіть опцію'"
                                :multiple="fieldConfig.multiple === true"
                                :searchable="fieldConfig.searchable === true"
                            />
                            <button-field
                                v-else-if="fieldConfig.type === 'button'"
                                v-model="formData.settings[fieldName]"
                                :label="fieldConfig.label"
                                :required="fieldConfig.required"
                                :sidebar-title="fieldConfig.sidebarTitle || 'Налаштування кнопки'"
                                class="block-form__button-field"
                            />
                            <image-field
                                v-else-if="fieldConfig.type === 'image'"
                                v-model="formData.settings[fieldName]"
                                :label="fieldConfig.label"
                                :required="fieldConfig.required"
                                :sidebar-title="fieldConfig.sidebarTitle || 'Налаштування зображення'"
                                :api-url="props.apiUrl"
                                :csrf-token="props.csrfToken"
                                class="block-form__image-field"
                            />
                            <wysiwyg-field
                                v-else-if="fieldConfig.type === 'wysiwyg'"
                                :ref="el => setWysiwygRef(el, `settings-${fieldName}`)"
                                v-model="formData.settings[fieldName]"
                                :placeholder="fieldConfig.placeholder || ''"
                                :height="fieldConfig.height || 400"
                                :api-url="props.apiUrl"
                                :csrf-token="props.csrfToken"
                                :key="`wysiwyg-settings-${fieldName}-${blockIndex}`"
                                class="block-form__wysiwyg"
                            />
                            <div v-if="fieldConfig.help" class="block-form__help">
                                {{ fieldConfig.help }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <div class="block-form__actions">
                    <div class="block-form__buttons">
                        <button type="button" @click="save" class="block-form__button block-form__button--primary">
                            Зберегти
                        </button>
                        <button
                            type="button"
                            @click="deleteBlock"
                            class="block-form__button block-form__button--danger"
                        >
                            Видалити
                        </button>
                    </div>
                <!-- Notification Message -->
                <transition name="fade">
                    <div v-if="notification.show" :class="['block-form__notification', `block-form__notification--${notification.type}`]">
                        {{ notification.message }}
                    </div>
                </transition>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted, onUpdated, nextTick } from 'vue';
import ButtonField from './ButtonField.vue';
import ImageField from './ImageField.vue';
import RepeaterField from './RepeaterField.vue';
import SelectField from './SelectField.vue';
import WysiwygField from './WysiwygField.vue';

export default {
    name: 'BlockForm',
    components: {
        ButtonField,
        ImageField,
        RepeaterField,
        SelectField,
        WysiwygField,
    },
    props: {
        block: {
            type: Object,
            required: true,
        },
        blockIndex: {
            type: Number,
            required: true,
        },
        blockType: {
            type: String,
            required: true,
        },
        availableBlocks: {
            type: Object,
            required: true,
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
    emits: ['save', 'delete'],
    setup(props, { emit }) {
        const blockConfig = ref(props.availableBlocks[props.blockType] || null);
        const notification = ref({ show: false, message: '', type: 'success' });
        const wysiwygRefs = ref({});
        const lastReinitTime = ref(0);
        const settingsExpanded = ref(blockConfig.value?.settings_collapsed !== true);
        const formData = reactive({
            id: props.block.id || `block-${Date.now()}`,
            type: props.blockType,
            order: props.block.order || props.blockIndex + 1,
            settings: { ...(props.block.settings || blockConfig.value?.default_settings || {}) },
            data: { ...(props.block.data || blockConfig.value?.default_data || {}) },
        });

        /**
         * Set wysiwyg field reference.
         */
        const setWysiwygRef = (el, key) => {
            if (el) {
                wysiwygRefs.value[key] = el;
            } else {
                delete wysiwygRefs.value[key];
            }
        };

        /**
         * Reinitialize all wysiwyg editors.
         */
        const reinitWysiwygEditors = async () => {
            const now = Date.now();
            // Throttle reinitialization to avoid too frequent calls
            if (now - lastReinitTime.value < 500) {
                return;
            }
            lastReinitTime.value = now;

            await nextTick();
            Object.values(wysiwygRefs.value).forEach((wysiwygField) => {
                if (wysiwygField && typeof wysiwygField.reinitEditor === 'function') {
                    wysiwygField.reinitEditor();
                }
            });
        };

        /**
         * Get data fields from block config.
         */
        const dataFields = computed(() => {
            if (!blockConfig.value || !blockConfig.value.fields) {
                return {};
            }
            return blockConfig.value.fields.data || {};
        });

        /**
         * Get settings fields from block config.
         */
        const settingsFields = computed(() => {
            if (!blockConfig.value || !blockConfig.value.fields) {
                return {};
            }
            return blockConfig.value.fields.settings || {};
        });

        /**
         * Reinitialize wysiwyg editors when component is updated (e.g., when block is expanded).
         */
        onUpdated(() => {
            // Check if any wysiwyg field exists and is not initialized
            const hasWysiwygFields = Object.keys(wysiwygRefs.value).length > 0;
            if (hasWysiwygFields) {
                // Small delay to ensure DOM is fully rendered
                setTimeout(() => {
                    reinitWysiwygEditors();
                }, 300);
            }
        });

        /**
         * Initialize form data with default values from fields.
         */
        onMounted(() => {
            // Ensure all fields from config have values in formData
            if (dataFields.value) {
                Object.keys(dataFields.value).forEach((fieldName) => {
                    const fieldConfig = dataFields.value[fieldName];
                    if (fieldConfig.type === 'select' && fieldConfig.multiple === true && !Array.isArray(formData.data[fieldName])) {
                        formData.data[fieldName] = formData.data[fieldName]
                            ? Array.isArray(formData.data[fieldName]) ? formData.data[fieldName] : [formData.data[fieldName]]
                            : (fieldConfig.default ?? []);
                        return;
                    }

                    if (!(fieldName in formData.data)) {
                        if (fieldConfig.type === 'number') {
                            formData.data[fieldName] = fieldConfig.default ?? 0;
                        } else if (fieldConfig.type === 'button') {
                            formData.data[fieldName] = fieldConfig.default ?? { text: '', url: '', openInNewTab: false };
                        } else if (fieldConfig.type === 'image') {
                            formData.data[fieldName] = fieldConfig.default ?? { url: '', alt: '', width: null, height: null, class: '', lazy: false };
                        } else if (fieldConfig.type === 'repeater') {
                            formData.data[fieldName] = fieldConfig.default ?? [];
                        } else if (fieldConfig.type === 'select' && fieldConfig.multiple === true) {
                            formData.data[fieldName] = fieldConfig.default ?? [];
                        } else {
                            formData.data[fieldName] = fieldConfig.default ?? '';
                        }
                    }
                });
            }

            if (settingsFields.value) {
                Object.keys(settingsFields.value).forEach((fieldName) => {
                    const fieldConfig = settingsFields.value[fieldName];
                    if (fieldConfig.type === 'select' && fieldConfig.multiple === true && !Array.isArray(formData.settings[fieldName])) {
                        formData.settings[fieldName] = formData.settings[fieldName]
                            ? Array.isArray(formData.settings[fieldName]) ? formData.settings[fieldName] : [formData.settings[fieldName]]
                            : (fieldConfig.default ?? []);
                        return;
                    }

                    if (!(fieldName in formData.settings)) {
                        if (fieldConfig.type === 'number') {
                            formData.settings[fieldName] = fieldConfig.default ?? 0;
                        } else if (fieldConfig.type === 'button') {
                            formData.settings[fieldName] = fieldConfig.default ?? { text: '', url: '', openInNewTab: false };
                        } else if (fieldConfig.type === 'image') {
                            formData.settings[fieldName] = fieldConfig.default ?? { url: '', alt: '', width: null, height: null, class: '', lazy: false };
                        } else if (fieldConfig.type === 'repeater') {
                            formData.settings[fieldName] = fieldConfig.default ?? [];
                        } else if (fieldConfig.type === 'select' && fieldConfig.multiple === true) {
                            formData.settings[fieldName] = fieldConfig.default ?? [];
                        } else {
                            formData.settings[fieldName] = fieldConfig.default ?? '';
                        }
                    }
                });
            }
        });

        /**
         * Show notification.
         */
        const showNotification = (message, type = 'success') => {
            console.log('BlockForm showNotification called:', message, type);
            notification.value = { show: true, message, type };
            setTimeout(() => {
                notification.value.show = false;
            }, 3000);
        };

        /**
         * Save block.
         */
        const save = (event) => {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            emit('save', formData, props.blockIndex, showNotification);
        };

        /**
         * Delete block.
         */
        const deleteBlock = () => {
            emit('delete', props.blockIndex);
        };

        return {
            props,
            blockConfig,
            settingsExpanded,
            formData,
            dataFields,
            settingsFields,
            notification,
            wysiwygRefs,
            setWysiwygRef,
            reinitWysiwygEditors,
            showNotification,
            save,
            deleteBlock,
        };
    },
};
</script>

<style scoped>
.block-form {
    padding: 1rem;
}

.block-form__fields {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.block-form__section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.block-form__section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.block-form__section-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-align: left;
    background: transparent;
    border-top: 0;
    border-left: 0;
    border-right: 0;
    cursor: pointer;
    font-family: inherit;
}

.block-form__section-toggle-icon {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

.block-form__section-toggle-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.block-form__section-toggle-icon-image {
    width: 1.5rem;
    height: 1.5rem;
    object-fit: contain;
}

.block-form__section-toggle-icon--expanded {
    transform: rotate(180deg);
}

.block-form__section-fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.block-form__field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.block-form__label {
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
}

.block-form__required {
    color: #dc2626;
}

.block-form__input,
.block-form__textarea,
.block-form__select {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.block-form__input:focus,
.block-form__textarea:focus,
.block-form__select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.block-form__input--color {
    width: 100%;
    height: 2.5rem;
    padding: 0.25rem;
    cursor: pointer;
}

.block-form__textarea {
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
}

.block-form__select {
    background-color: #ffffff;
    cursor: pointer;
}

.block-form__help {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: -0.25rem;
}

.block-form__actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.block-form__button {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background-color 0.2s;
    font-weight: 500;
}

.block-form__button--primary {
    background-color: #2563eb;
    color: #ffffff;
}

.block-form__button--primary:hover {
    background-color: #1d4ed8;
}

.block-form__button--danger {
    background-color: #dc2626;
    color: #ffffff;
}

.block-form__button--danger:hover {
    background-color: #b91c1c;
}
</style>
