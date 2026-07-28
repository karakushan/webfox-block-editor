<template>
    <div class="wysiwyg-field">
        <textarea ref="textareaRef" :id="editorId" :value="modelValue" :placeholder="placeholder"
            class="wysiwyg-field__textarea"></textarea>
    </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch, nextTick, onUpdated } from 'vue';
import tinymce from 'tinymce/tinymce';

// Import TinyMCE theme
import 'tinymce/themes/silver';

// Import TinyMCE plugins
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/directionality';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/help';
import 'tinymce/plugins/image';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/nonbreaking';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/quickbars';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/visualchars';
import 'tinymce/plugins/wordcount';

// Import TinyMCE UI skin CSS only (content CSS is loaded via content_css option in iframe)
import 'tinymce/skins/ui/oxide/skin.min.css';
// Note: Do NOT import content.min.css here - it contains body styles that leak to the main page
// Content styles are applied inside TinyMCE's iframe via content_css option

export default {
    name: 'WysiwygField',
    props: {
        modelValue: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
        height: {
            type: Number,
            default: 400,
        },
        apiUrl: {
            type: String,
            required: false,
            default: '',
        },
        csrfToken: {
            type: String,
            required: false,
            default: '',
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit, expose }) {
        const textareaRef = ref(null);
        const wrapperRef = ref(null);
        const editorId = ref(`wysiwyg-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`);
        let editorInstance = null;
        let isInitialized = false;

        /**
         * Check if element is visible.
         */
        const isElementVisible = (element) => {
            if (!element) {
                return false;
            }
            const style = window.getComputedStyle(element);
            return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
        };

        /**
         * Initialize TinyMCE editor.
         */
        const initEditor = async () => {
            // Wait for multiple ticks to ensure DOM is ready
            await nextTick();
            await nextTick();
            await new Promise(resolve => setTimeout(resolve, 50));

            // Try to get element by ref or by ID as fallback
            let element = textareaRef.value;
            if (!element) {
                // Fallback: try to find element by ID
                element = document.getElementById(editorId.value);
            }

            if (!element) {
                // Retry after a delay
                setTimeout(() => {
                    if (!isInitialized) {
                        initEditor();
                    }
                }, 200);
                return;
            }

            // Check if TinyMCE is available
            const tmce = window.tinymce || tinymce;
            if (typeof tmce === 'undefined' || !tmce) {
                return;
            }

            // Check element visibility
            // TinyMCE can initialize on hidden elements and will show when element becomes visible
            const visible = isElementVisible(element);

            // Initialize even if not visible - TinyMCE handles hidden elements

            // If already initialized, don't initialize again
            if (isInitialized) {
                return;
            }

            try {
                const tmce = window.tinymce || tinymce;

                // Configure base URL for TinyMCE assets
                // In Vite, we need to use import.meta.url to get the correct path
                const getTinyMCEBaseUrl = () => {
                    // Try to get path from import.meta.url if available
                    if (typeof import.meta !== 'undefined' && import.meta.url) {
                        try {
                            const url = new URL(import.meta.url);
                            // Find the path to node_modules/tinymce
                            return url.origin + '/node_modules/tinymce';
                        } catch (e) {
                            // Ignore error
                        }
                    }
                    // Fallback: try to detect from script tags
                    const scripts = Array.from(document.getElementsByTagName('script'));
                    for (let script of scripts) {
                        if (script.src && script.src.includes('block-editor')) {
                            const url = new URL(script.src);
                            return url.origin + '/node_modules/tinymce';
                        }
                    }
                    // Last fallback
                    return '/node_modules/tinymce';
                };

                // Use public vendor path for TinyMCE assets
                const baseUrl = '/vendor/tinymce';

                // Get CSRF token
                const getCsrfToken = () => {
                    if (props.csrfToken) {
                        return props.csrfToken;
                    }
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
                    return '';
                };

                // Configure image upload URL
                const imageUploadUrl = props.apiUrl
                    ? `${props.apiUrl}/images/upload-tinymce`
                    : '';

                const initResult = await tmce.init({
                    target: element,
                    height: props.height,
                    menubar: false,
                    base_url: baseUrl,
                    suffix: '.min',
                    license_key: 'gpl',
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'autoresize',
                        'emoticons', 'codesample', 'pagebreak', 'nonbreaking',
                        'directionality', 'visualchars', 'quickbars'
                    ],
                    toolbar: [
                        'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify',
                        'bullist numlist | outdent indent | link image media table | blockquote code | removeformat help'
                    ],
                    // Context menu configuration
                    contextmenu: 'link image table | bullist numlist | copy paste | removeformat',
                    // Quickbars selection toolbar (shows when text is selected)
                    quickbars_selection_toolbar: 'bold italic underline | bullist numlist | quicklink blockquote',
                    quickbars_insert_toolbar: false,
                    table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
                    table_appearance_options: false,
                    table_grid: true,
                    table_resize_bars: true,
                    table_default_attributes: {
                        border: '1'
                    },
                    table_default_styles: {
                        borderCollapse: 'collapse',
                        width: '100%'
                    },
                    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre; Blockquote=blockquote',
                    formats: {
                        alignleft: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', classes: 'text-left' },
                        aligncenter: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', classes: 'text-center' },
                        alignright: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', classes: 'text-right' },
                        alignjustify: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', classes: 'text-justify' },
                        bold: { inline: 'strong', classes: 'font-bold' },
                        italic: { inline: 'em', classes: 'italic' },
                        underline: { inline: 'u', classes: 'underline' },
                        strikethrough: { inline: 'del' },
                    },
                    valid_elements: '*[*]',
                    extended_valid_elements: 'script[src|async|defer|type|charset],style[type],iframe[src|width|height|name|id|class|style|scrolling|marginwidth|marginheight|frameborder],object[width|height|classid|codebase|data|type|id],param[name|value],embed[src|type|width|height|wmode|allowscriptaccess|allowfullscreen],video[src|width|height|controls|poster|preload|autoplay|loop|muted],audio[src|controls|preload|autoplay|loop],source[src|type],track[kind|src|srclang|label|default]',
                    invalid_elements: '',
                    paste_as_text: false,
                    paste_data_images: true,
                    paste_remove_styles_if_webkit: false,
                    paste_retain_style_properties: 'color font-size font-family background-color',
                    paste_webkit_styles: 'color font-size font-family background-color',
                    paste_merge_formats: true,
                    convert_urls: false,
                    relative_urls: false,
                    remove_script_host: false,
                    document_base_url: window.location.origin,
                    // Load content CSS inside TinyMCE iframe only (not globally)
                    // This prevents body styles from leaking to the main admin page
                    content_css: baseUrl + '/skins/content/default/content.min.css',
                    content_style: 'body { margin: 0 !important; }',
                    body_class: 'mce-content-body',
                    body_id: 'tinymce-editor-body',
                    placeholder: props.placeholder,
                    promotion: false,
                    branding: false,
                    images_upload_url: imageUploadUrl,
                    images_file_types: 'jpg,jpeg,png,gif,webp,svg,avif',
                    images_upload_handler: imageUploadUrl ? async (blobInfo, progress) => {
                        return new Promise((resolve, reject) => {
                            const formData = new FormData();
                            // Get file extension from filename
                            const filename = blobInfo.filename();
                            // Create blob with proper filename extension
                            const blob = blobInfo.blob();
                            const file = new File([blob], filename, { type: blob.type });
                            formData.append('file', file);

                            const xhr = new XMLHttpRequest();
                            xhr.withCredentials = false;
                            xhr.open('POST', imageUploadUrl);

                            // Set CSRF token header
                            const csrfToken = getCsrfToken();
                            if (csrfToken) {
                                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                            }
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                            xhr.upload.onprogress = (e) => {
                                if (e.lengthComputable) {
                                    progress((e.loaded / e.total) * 100);
                                }
                            };

                            xhr.onload = () => {
                                if (xhr.status === 403) {
                                    reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                                    return;
                                }

                                if (xhr.status < 200 || xhr.status >= 300) {
                                    reject('HTTP Error: ' + xhr.status);
                                    return;
                                }

                                const json = JSON.parse(xhr.responseText);

                                if (!json || typeof json.location !== 'string') {
                                    reject('Invalid JSON: ' + xhr.responseText);
                                    return;
                                }

                                resolve(json.location);
                            };

                            xhr.onerror = () => {
                                reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                            };

                            xhr.send(formData);
                        });
                    } : undefined,
                    setup: (editor) => {
                        editor.on('init', () => {
                            isInitialized = true;
                            editorInstance = editor;

                            if (props.modelValue) {
                                editor.setContent(props.modelValue);
                            }
                        });

                        editor.on('input', () => {
                            const content = editor.getContent();
                            emit('update:modelValue', content);
                        });

                        editor.on('change', () => {
                            const content = editor.getContent();
                            emit('update:modelValue', content);
                        });
                    },
                });
            } catch (error) {
                isInitialized = false;
            }
        };

        /**
         * Reinitialize editor if needed.
         */
        const reinitEditor = async () => {
            // Remove existing editor if present
            if (editorInstance) {
                try {
                    const tmce = window.tinymce || tinymce;
                    const editorId = editorInstance.id;
                    await tmce.remove(editorId);
                    editorInstance = null;
                    isInitialized = false;
                } catch (error) {
                    // Try alternative removal method
                    try {
                        const tmce = window.tinymce || tinymce;
                        if (textareaRef.value) {
                            const editorId = textareaRef.value.id;
                            await tmce.remove(`#${editorId}`);
                        }
                    } catch (e) {
                        // Ignore error
                    }
                    editorInstance = null;
                    isInitialized = false;
                }
            }

            // Wait a bit for cleanup to complete
            await nextTick();
            await new Promise(resolve => setTimeout(resolve, 100));

            // Reinitialize editor
            initEditor();
        };

        /**
         * Watch for external changes.
         */
        watch(() => props.modelValue, (newValue) => {
            if (editorInstance && isInitialized) {
                const currentContent = editorInstance.getContent();
                if (currentContent !== newValue) {
                    editorInstance.setContent(newValue || '');
                }
            }
        });

        /**
         * Watch for visibility changes and reinitialize if needed.
         */
        onUpdated(() => {
            const element = textareaRef.value || document.getElementById(editorId.value);
            if (!element) {
                return;
            }

            const isVisible = isElementVisible(element);

            if (!isInitialized) {
                // Initialize editor if not already initialized (even if not visible)
                setTimeout(() => {
                    const checkElement = textareaRef.value || document.getElementById(editorId.value);
                    if (!isInitialized && checkElement) {
                        initEditor();
                    }
                }, 200);
            } else if (isInitialized && editorInstance && isVisible) {
                // Check if editor needs to be refreshed when it becomes visible
                try {
                    // Force repaint to ensure editor is properly displayed
                    editorInstance.execCommand('mceRepaint');
                    // Also trigger resize to recalculate dimensions
                    if (editorInstance.plugins && editorInstance.plugins.autoresize) {
                        editorInstance.execCommand('mceAutoResize');
                    }
                } catch (error) {
                    // Ignore errors, editor might not be fully ready
                }
            }
        });

        /**
         * Initialize editor on mount.
         */
        onMounted(async () => {
            // Wait for ref to be set
            await nextTick();
            await nextTick();

            // Get element by ref or by ID
            const getElement = () => {
                return textareaRef.value || document.getElementById(editorId.value);
            };

            const element = getElement();

            // Use IntersectionObserver to detect when element becomes visible
            if (element && 'IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && !isInitialized) {
                            initEditor();
                            observer.disconnect();
                        }
                    });
                }, {
                    threshold: 0.1,
                });

                observer.observe(element);

                // Fallback: try initialization after a delay
                setTimeout(() => {
                    const checkElement = getElement();
                    // Initialize even if not visible - TinyMCE can handle hidden elements
                    if (!isInitialized && checkElement) {
                        initEditor();
                    }
                    observer.disconnect();
                }, 500);
            } else {
                // Fallback for browsers without IntersectionObserver or if element not found
                setTimeout(() => {
                    initEditor();
                }, 300);
            }
        });

        /**
         * Cleanup editor on unmount.
         */
        onBeforeUnmount(() => {
            if (editorInstance) {
                try {
                    const tmce = window.tinymce || tinymce;
                    tmce.remove(editorInstance);
                } catch (error) {
                    // Ignore error
                }
                editorInstance = null;
                isInitialized = false;
            }
        });

        // Expose reinitEditor method for parent components
        expose({
            reinitEditor,
        });

        return {
            textareaRef,
            wrapperRef,
            editorId,
            reinitEditor,
        };
    },
};
</script>

<style scoped>
.wysiwyg-field {
    width: 100%;
}

.wysiwyg-field__textarea {
    width: 100%;
    min-height: 400px;
    visibility: hidden;
}
</style>
