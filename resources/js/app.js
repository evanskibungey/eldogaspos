import './bootstrap';
import './offline/OfflinePOSManager.js';
import './service-worker-manager.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Initialize offline POS manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof OfflinePOSManager !== 'undefined') {
        window.offlinePOS = new OfflinePOSManager();
        console.log('Offline POS Manager initialized globally');
    }
});
