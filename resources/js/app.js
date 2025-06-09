import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Check if offline mode is enabled (this will be set by the blade template)
const isOfflineModeEnabled = window.offlineModeEnabled || false;

if (isOfflineModeEnabled) {
    // Only load offline components if offline mode is enabled
    import('./offline/OfflinePOSManager.js').then(() => {
        console.log('Offline POS Manager loaded');
        
        // Initialize offline POS manager when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof OfflinePOSManager !== 'undefined') {
                window.offlinePOS = new OfflinePOSManager();
                console.log('Offline POS Manager initialized globally');
            }
        });
    });
    
    import('./service-worker-manager.js').then(() => {
        console.log('Service Worker Manager loaded');
    });
} else {
    console.log('Offline mode is disabled - running in online-only mode');
}

Alpine.start();
