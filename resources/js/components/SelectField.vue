<template>
    <div ref="root" class="select-field" :class="{ 'select-field--open': isOpen, 'select-field--multiple': multiple }">
        <button
            type="button"
            class="select-field__trigger"
            :class="{ 'select-field__trigger--placeholder': !hasValue }"
            @click="toggleDropdown"
        >
            <span class="select-field__trigger-text">
                {{ triggerLabel }}
            </span>
            <svg class="select-field__trigger-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div v-if="isOpen" class="select-field__dropdown">
            <div v-if="showSearch" class="select-field__search">
                <input
                    v-model="searchQuery"
                    type="text"
                    class="select-field__search-input"
                    placeholder="Пошук..."
                />
            </div>

            <div class="select-field__options">
                <button
                    v-if="!multiple"
                    type="button"
                    class="select-field__option"
                    :class="{ 'select-field__option--selected': modelValue === '' || modelValue === null || modelValue === undefined }"
                    @click="selectSingle('')"
                >
                    <span class="select-field__option-label">{{ placeholderText }}</span>
                </button>

                <button
                    v-for="option in filteredOptions"
                    :key="String(option.value)"
                    type="button"
                    class="select-field__option"
                    :class="{ 'select-field__option--selected': isSelected(option.value) }"
                    @click="selectOption(option.value)"
                >
                    <span v-if="multiple" class="select-field__checkbox" :class="{ 'select-field__checkbox--selected': isSelected(option.value) }">
                        <svg v-if="isSelected(option.value)" class="select-field__checkbox-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </span>
                    <span class="select-field__option-label">{{ option.label }}</span>
                </button>

                <div v-if="filteredOptions.length === 0" class="select-field__empty">
                    Нічого не знайдено
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

export default {
    name: 'SelectField',
    props: {
        modelValue: {
            type: [String, Number, Array],
            default: '',
        },
        options: {
            type: Array,
            default: () => [],
        },
        placeholder: {
            type: String,
            default: 'Виберіть опцію',
        },
        multiple: {
            type: Boolean,
            default: false,
        },
        searchable: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['update:model-value'],
    setup(props, { emit }) {
        const root = ref(null);
        const isOpen = ref(false);
        const searchQuery = ref('');

        const normalizedOptions = computed(() =>
            (props.options || []).map((option) => ({
                value: option?.value ?? '',
                label: option?.label ?? String(option?.value ?? ''),
            }))
        );

        const selectedValues = computed(() => {
            if (!props.multiple) {
                return [];
            }

            if (Array.isArray(props.modelValue)) {
                return props.modelValue.map((value) => String(value));
            }

            if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
                return [];
            }

            return [String(props.modelValue)];
        });

        const showSearch = computed(() => props.searchable || props.multiple || normalizedOptions.value.length > 8);

        const filteredOptions = computed(() => {
            const query = searchQuery.value.trim().toLowerCase();
            if (!query) {
                return normalizedOptions.value;
            }

            return normalizedOptions.value.filter((option) => option.label.toLowerCase().includes(query));
        });

        const hasValue = computed(() => {
            if (props.multiple) {
                return selectedValues.value.length > 0;
            }

            return ![null, undefined, ''].includes(props.modelValue);
        });

        const triggerLabel = computed(() => {
            if (props.multiple) {
                if (selectedValues.value.length === 0) {
                    return props.placeholder;
                }

                const selectedLabels = normalizedOptions.value
                    .filter((option) => selectedValues.value.includes(String(option.value)))
                    .map((option) => option.label);

                if (selectedLabels.length <= 2) {
                    return selectedLabels.join(', ');
                }

                return `Обрано: ${selectedLabels.length}`;
            }

            const selectedOption = normalizedOptions.value.find((option) => String(option.value) === String(props.modelValue));
            return selectedOption?.label ?? props.placeholder;
        });

        const placeholderText = computed(() => props.placeholder || 'Виберіть опцію');

        const closeDropdown = () => {
            isOpen.value = false;
            searchQuery.value = '';
        };

        const toggleDropdown = () => {
            isOpen.value = !isOpen.value;
            if (!isOpen.value) {
                searchQuery.value = '';
            }
        };

        const isSelected = (value) => {
            if (props.multiple) {
                return selectedValues.value.includes(String(value));
            }

            return String(props.modelValue) === String(value);
        };

        const selectSingle = (value) => {
            emit('update:model-value', value);
            closeDropdown();
        };

        const selectOption = (value) => {
            if (!props.multiple) {
                selectSingle(value);
                return;
            }

            const currentValues = [...selectedValues.value];
            const stringValue = String(value);
            const nextValues = currentValues.includes(stringValue)
                ? currentValues.filter((item) => item !== stringValue)
                : [...currentValues, stringValue];

            emit('update:model-value', nextValues);
        };

        const handleClickOutside = (event) => {
            if (root.value && !root.value.contains(event.target)) {
                closeDropdown();
            }
        };

        onMounted(() => {
            document.addEventListener('click', handleClickOutside);
        });

        onBeforeUnmount(() => {
            document.removeEventListener('click', handleClickOutside);
        });

        return {
            root,
            isOpen,
            searchQuery,
            filteredOptions,
            showSearch,
            hasValue,
            triggerLabel,
            placeholderText,
            toggleDropdown,
            selectOption,
            selectSingle,
            isSelected,
        };
    },
};
</script>

<style scoped>
.select-field {
    position: relative;
}

.select-field__trigger {
    width: 100%;
    min-height: 2.75rem;
    padding: 0.75rem 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.75rem;
    background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
}

.select-field__trigger:hover {
    border-color: #9ca3af;
}

.select-field--open .select-field__trigger {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    background: #ffffff;
}

.select-field__trigger--placeholder {
    color: #9ca3af;
}

.select-field__trigger-text {
    flex: 1;
    min-width: 0;
    font-size: 0.875rem;
    line-height: 1.4;
    color: inherit;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.select-field__trigger-icon {
    width: 1rem;
    height: 1rem;
    color: #6b7280;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

.select-field--open .select-field__trigger-icon {
    transform: rotate(180deg);
}

.select-field__dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    z-index: 50;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    overflow: hidden;
}

.select-field__search {
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}

.select-field__search-input {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.625rem;
    font-size: 0.875rem;
}

.select-field__search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.select-field__options {
    max-height: 18rem;
    overflow-y: auto;
    padding: 0.5rem;
}

.select-field__option {
    width: 100%;
    border: none;
    background: transparent;
    padding: 0.75rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    text-align: left;
    transition: background-color 0.15s, color 0.15s;
}

.select-field__option:hover {
    background: #f3f4f6;
}

.select-field__option--selected {
    background: #eff6ff;
    color: #1d4ed8;
}

.select-field__checkbox {
    width: 1.125rem;
    height: 1.125rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #ffffff;
}

.select-field__checkbox--selected {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

.select-field__checkbox-icon {
    width: 0.875rem;
    height: 0.875rem;
}

.select-field__option-label {
    font-size: 0.875rem;
    line-height: 1.4;
    color: inherit;
}

.select-field__empty {
    padding: 0.875rem 0.75rem;
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
