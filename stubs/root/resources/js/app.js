import './bootstrap';
import './hero-stars';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { createIcons, icons } from 'lucide';
import '@fortawesome/fontawesome-free/css/all.css';

// Initialize Lucide Icons
window.lucide = { createIcons, icons };
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
});

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const link = event.target.closest('[data-site-event]');
    if (!link) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        return;
    }

    const payload = {
        _token: csrfToken,
        event_name: link.dataset.siteEvent || 'site_event',
        event_type: link.dataset.siteEventType || 'interaction',
        path: window.location.pathname,
        target_url: link.dataset.siteTargetUrl || link.href || null,
        catalog_item_code: link.dataset.siteCatalogCode || null,
    };

    const body = JSON.stringify(payload);
    const url = '/site-events';

    if (navigator.sendBeacon) {
        navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
        return;
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body,
        keepalive: true,
    }).catch(() => {});
});

// Initialize Alpine.js
Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();
