import { createApp } from 'vue';
import BookingWidget from './BookingWidget.vue';
import './widget.css';

// Функция инициализации виджета
window.BookingStack = {
    init(selector, options = {}) {
        const container = document.querySelector(selector);
        if (!container) {
            console.error(`BookingStack: Element "${selector}" not found`);
            return;
        }

        const tenantSlug = options.tenant || container.dataset.tenant;
        if (!tenantSlug) {
            console.error('BookingStack: tenant is required');
            return;
        }

        const app = createApp(BookingWidget, {
            tenant: tenantSlug,
            apiUrl: options.apiUrl || 'http://localhost/api/v1',
            primaryColor: options.primaryColor || null,
        });

        app.mount(container);
        return app;
    }
};

// Auto-init если есть элемент с data-bookingstack
document.addEventListener('DOMContentLoaded', () => {
    const autoInit = document.querySelector('[data-bookingstack]');
    if (autoInit) {
        const tenant = autoInit.dataset.tenant;
        if (tenant) {
            window.BookingStack.init('[data-bookingstack]', { tenant });
        }
    }
});