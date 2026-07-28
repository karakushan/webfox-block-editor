import { createApp } from 'vue';
import BlockEditor from '../components/BlockEditor.vue';

/**
 * Stimulus controller for Block Editor Vue component.
 */
export default class extends window.Controller {
    /**
     * Connect the controller.
     */
    connect() {
        const modelId = this.data.get('modelId');
        const props = {
            modelId: modelId ? parseInt(modelId, 10) : null,
            modelClass: this.data.get('modelClass'),
            contentField: this.data.get('contentField') || 'content',
            locale: this.data.get('locale') || 'uk',
            apiUrl: this.data.get('apiUrl'),
            csrfToken: this.data.get('csrfToken') || this.getCsrfToken(),
        };

        this.app = createApp(BlockEditor, props);
        this.app.mount(this.element);
    }

    /**
     * Get CSRF token from meta tag or cookie.
     */
    getCsrfToken() {
        // Try meta tag first
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (metaToken) {
            return metaToken;
        }

        // Try XSRF cookie (Laravel default)
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                return decodeURIComponent(value);
            }
        }

        return null;
    }

    /**
     * Disconnect the controller.
     */
    disconnect() {
        if (this.app) {
            this.app.unmount();
        }
    }
}

