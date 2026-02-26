import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

const resetSidebarOverlayState = () => {
    const sidebar = window.document.querySelector('.nk-sidebar');
    if (! sidebar) {
        return;
    }

    sidebar.classList.remove('sidebar-visible');
    window.document.body.classList.remove('overflow-hidden');
    window.document.querySelectorAll('.sidebar-toggle.active').forEach((button) => {
        button.classList.remove('active');
    });
};

const fallbackToBrowserVisit = (event) => {
    event.preventDefault();
    resetSidebarOverlayState();

    const responseUrl = event?.detail?.response?.url;
    const visitUrl = event?.detail?.visit?.url;
    const targetUrl = responseUrl || visitUrl;

    if (targetUrl) {
        window.location.href = targetUrl;

        return;
    }

    window.location.reload();
};

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

router.on('start', () => {
    resetSidebarOverlayState();
});

router.on('success', () => {
    resetSidebarOverlayState();
    window.dispatchEvent(new Event('ui:inertia-success'));
});

router.on('invalid', fallbackToBrowserVisit);
