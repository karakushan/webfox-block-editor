<template>
    <div class="button-field">
        <div class="button-field__preview" @click="openSidebar">
            <div v-if="buttonData.url || buttonData.text" class="button-field__preview-content">
                <span class="button-field__preview-text">{{ buttonData.text || 'Текст кнопки' }}</span>
                <span v-if="buttonData.url" class="button-field__preview-url">{{ buttonData.url }}</span>
                <span v-if="buttonData.openInNewTab" class="button-field__preview-badge">Нове вікно</span>
            </div>
            <div v-else class="button-field__preview-placeholder">
                <svg class="button-field__preview-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Налаштувати кнопку</span>
            </div>
        </div>

        <!-- Sidebar Overlay -->
        <transition name="fade">
            <div v-if="showSidebar" class="button-field__overlay" @click="closeSidebar"></div>
        </transition>

        <!-- Sidebar -->
        <transition name="slide-right">
            <div v-if="showSidebar" class="button-field__sidebar">
                <div class="button-field__sidebar-header">
                    <h3 class="button-field__sidebar-title">{{ sidebarTitle }}</h3>
                    <button type="button" @click="closeSidebar" class="button-field__sidebar-close">
                        <svg class="button-field__sidebar-close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="button-field__sidebar-content">
                    <div class="button-field__sidebar-field">
                        <label class="button-field__sidebar-label">
                            Текст кнопки
                            <span v-if="required" class="button-field__required">*</span>
                        </label>
                        <input
                            v-model="buttonData.text"
                            type="text"
                            :required="required"
                            placeholder="Введіть текст кнопки"
                            class="button-field__sidebar-input"
                        />
                    </div>

                    <div class="button-field__sidebar-field">
                        <label class="button-field__sidebar-label">
                            URL
                            <span v-if="required" class="button-field__required">*</span>
                        </label>
                        <input
                            v-model="buttonData.url"
                            type="url"
                            :required="required"
                            placeholder="https://example.com"
                            class="button-field__sidebar-input"
                        />
                    </div>

                    <div class="button-field__sidebar-field">
                        <label class="button-field__sidebar-label">
                            <input
                                v-model="buttonData.openInNewTab"
                                type="checkbox"
                                class="button-field__sidebar-checkbox"
                            />
                            <span>Відкривати в новому вікні</span>
                        </label>
                    </div>

                    <div class="button-field__sidebar-actions">
                        <button type="button" @click="saveButton" class="button-field__sidebar-btn button-field__sidebar-btn--primary">
                            Зберегти
                        </button>
                        <button type="button" @click="clearButton" class="button-field__sidebar-btn button-field__sidebar-btn--secondary">
                            Очистити
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { ref, reactive, watch } from 'vue';

export default {
    name: 'ButtonField',
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
            default: 'Налаштування кнопки',
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const showSidebar = ref(false);
        const buttonData = reactive({
            text: (props.modelValue && props.modelValue.text) ? props.modelValue.text : '',
            url: (props.modelValue && props.modelValue.url) ? props.modelValue.url : '',
            openInNewTab: (props.modelValue && props.modelValue.openInNewTab) ? props.modelValue.openInNewTab : false,
        });

        /**
         * Watch for external changes.
         */
        watch(() => props.modelValue, (newValue) => {
            if (newValue && typeof newValue === 'object') {
                buttonData.text = newValue.text || '';
                buttonData.url = newValue.url || '';
                buttonData.openInNewTab = newValue.openInNewTab || false;
            } else {
                buttonData.text = '';
                buttonData.url = '';
                buttonData.openInNewTab = false;
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
         * Save button data.
         */
        const saveButton = () => {
            emit('update:modelValue', { ...buttonData });
            closeSidebar();
        };

        /**
         * Clear button data.
         */
        const clearButton = () => {
            buttonData.text = '';
            buttonData.url = '';
            buttonData.openInNewTab = false;
            emit('update:modelValue', {});
            closeSidebar();
        };

        return {
            showSidebar,
            buttonData,
            openSidebar,
            closeSidebar,
            saveButton,
            clearButton,
        };
    },
};
</script>

<style scoped>
.button-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.button-field__label {
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
}

.button-field__required {
    color: #dc2626;
}

.button-field__preview {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background-color: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 3rem;
    display: flex;
    align-items: center;
}

.button-field__preview:hover {
    border-color: #2563eb;
    background-color: #f9fafb;
}

.button-field__preview-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
}

.button-field__preview-text {
    font-weight: 500;
    color: #111827;
    font-size: 0.875rem;
}

.button-field__preview-url {
    font-size: 0.75rem;
    color: #6b7280;
}

.button-field__preview-badge {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    background-color: #eff6ff;
    color: #2563eb;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    width: fit-content;
}

.button-field__preview-placeholder {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #9ca3af;
    font-size: 0.875rem;
}

.button-field__preview-icon {
    width: 1.25rem;
    height: 1.25rem;
}

/* Sidebar Overlay */
.button-field__overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9998;
}

/* Sidebar */
.button-field__sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: 400px;
    max-width: 90vw;
    height: 100vh;
    background-color: #ffffff;
    box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    display: flex;
    flex-direction: column;
}

.button-field__sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.button-field__sidebar-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.button-field__sidebar-close {
    padding: 0.5rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    border-radius: 0.25rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.button-field__sidebar-close:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.button-field__sidebar-close-icon {
    width: 1.5rem;
    height: 1.5rem;
}

.button-field__sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.button-field__sidebar-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.button-field__sidebar-label {
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.button-field__sidebar-input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.button-field__sidebar-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.button-field__sidebar-checkbox {
    width: 1rem;
    height: 1rem;
    cursor: pointer;
}

.button-field__sidebar-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.button-field__sidebar-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color 0.2s;
}

.button-field__sidebar-btn--primary {
    background-color: #2563eb;
    color: #ffffff;
}

.button-field__sidebar-btn--primary:hover {
    background-color: #1d4ed8;
}

.button-field__sidebar-btn--secondary {
    background-color: #f3f4f6;
    color: #374151;
}

.button-field__sidebar-btn--secondary:hover {
    background-color: #e5e7eb;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.3s ease-in-out;
}

.slide-right-enter-from {
    transform: translateX(100%);
}

.slide-right-leave-to {
    transform: translateX(100%);
}
</style>

