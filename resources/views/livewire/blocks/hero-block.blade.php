<div class="hero-block-form space-y-4">
    <div class="border-b pb-4 mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Hero Block</h3>
        <p class="text-sm text-gray-500">Налаштуйте Hero секцію</p>
    </div>

    <div class="grid grid-cols-1 gap-4">
        {{-- Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Заголовок
            </label>
            <input
                type="text"
                wire:model.live="data.title"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Введіть заголовок"
            >
            @error('data.title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Subtitle --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Підзаголовок
            </label>
            <textarea
                wire:model.live="data.subtitle"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Введіть підзаголовок"
            ></textarea>
            @error('data.subtitle')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Image --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Зображення
            </label>
            <input
                type="text"
                wire:model.live="data.image"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="URL зображення або шлях"
            >
            @if($data['image'] ?? null)
                <div class="mt-2">
                    <img src="{{ $data['image'] }}" alt="Preview" class="max-w-xs h-auto rounded-lg border border-gray-300">
                </div>
            @endif
            @error('data.image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- CTA Text --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Текст кнопки CTA
            </label>
            <input
                type="text"
                wire:model.live="data.cta_text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Наприклад: Дізнатися більше"
            >
            @error('data.cta_text')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- CTA URL --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                URL кнопки CTA
            </label>
            <input
                type="text"
                wire:model.live="data.cta_url"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="https://example.com"
            >
            @error('data.cta_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Background Color --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Колір фону
            </label>
            <input
                type="color"
                wire:model.live="settings.background_color"
                class="w-full h-10 border border-gray-300 rounded-lg"
            >
            @error('settings.background_color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Text Color --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Колір тексту
            </label>
            <input
                type="color"
                wire:model.live="settings.text_color"
                class="w-full h-10 border border-gray-300 rounded-lg"
            >
            @error('settings.text_color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-2 pt-4 border-t">
        <button
            type="button"
            @click="$dispatch('close-block-editor')"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
        >
            Скасувати
        </button>
        <button
            type="button"
            wire:click="save"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
            Зберегти
        </button>
        @if($block)
            <button
                type="button"
                wire:click="delete"
                wire:confirm="Ви впевнені, що хочете видалити цей блок?"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
                Видалити
            </button>
        @endif
    </div>
</div>

