import BlockEditorController from "./controllers/block-editor_controller";
import "../css/block-editor.css";

// Register Stimulus controller
// Orchid uses Stimulus, and it should be available via window.application
// We'll register when DOM is ready and Stimulus is available
if (typeof window !== "undefined") {
    // Try to register immediately if Stimulus is already loaded
    if (window.application) {
        window.application.register("block-editor", BlockEditorController);
    } else {
        // Wait for Stimulus to load
        document.addEventListener("DOMContentLoaded", () => {
            // Check again after DOM is ready
            if (window.application) {
                window.application.register(
                    "block-editor",
                    BlockEditorController
                );
            } else {
                // Use Turbo event if available (Orchid uses Turbo)
                document.addEventListener("turbo:load", () => {
                    if (window.application) {
                        window.application.register(
                            "block-editor",
                            BlockEditorController
                        );
                    }
                });
            }
        });
    }
}
