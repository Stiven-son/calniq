import { createApp } from 'vue';
import BookingWidget from './BookingWidget.vue';
import PricingWidget from './components/PricingWidget.vue';
import './widget.css';

// Detect API base URL from script src at load time
const SCRIPT_BASE_URL = (function() {
    if (document.currentScript && document.currentScript.src) {
        try { return new URL(document.currentScript.src).origin; } catch(e) {}
    }
    const scripts = document.querySelectorAll('script[src*="bookingstack"]');
    if (scripts.length > 0) {
        try { return new URL(scripts[scripts.length - 1].src).origin; } catch(e) {}
    }
    const allScripts = document.querySelectorAll('script[src*="calniq"]');
    if (allScripts.length > 0) {
        try { return new URL(allScripts[allScripts.length - 1].src).origin; } catch(e) {}
    }
    return '';
})();

// Track initialized elements to prevent double-init
const initialized = new WeakSet();

// Public API
window.BookingStack = {
    init(selector, options = {}) {
        const container = typeof selector === 'string' 
            ? document.querySelector(selector) 
            : selector;
        
        if (!container) {
            console.error('BookingStack: Element not found:', selector);
            return null;
        }

        if (initialized.has(container)) {
            return null;
        }

        const widgetType = options.type || container.dataset.bookingstack || 'booking';
        const tenantSlug = options.tenant || container.dataset.tenant || 
            (widgetType === 'booking' ? container.dataset.bookingstack : null);
        
        if (!tenantSlug || tenantSlug === 'pricing') {
            // For pricing widget, tenant must be explicit
            const tenant = options.tenant || container.dataset.tenant;
            if (!tenant) {
                console.error('BookingStack: tenant is required');
                return null;
            }
        }

        const apiUrl = options.apiUrl || container.dataset.api || SCRIPT_BASE_URL + '/api/v1';
        const tenant = options.tenant || container.dataset.tenant || container.dataset.bookingstack;

        let app;

        if (widgetType === 'pricing') {
            const category = options.category || container.dataset.category;
            if (!category) {
                console.error('BookingStack: category is required for pricing widget');
                return null;
            }

            app = createApp(PricingWidget, {
                tenant: tenant,
                category: category,
                apiUrl: apiUrl,
                bookingUrl: options.bookingUrl || container.dataset.bookingUrl || '',
                primaryColor: options.primaryColor || container.dataset.primaryColor || null,
            });
        } else {
            app = createApp(BookingWidget, {
                tenant: tenant,
                apiUrl: apiUrl,
                primaryColor: options.primaryColor || null,
            });
        }

        initialized.add(container);
        app.mount(container);
        return app;
    },

    open(tenant) {
        console.log('BookingStack popup mode coming soon. Tenant:', tenant);
    },

    // Called by PricingWidget when on same page as BookingWidget
    preselect(preselectString) {
        // Find the booking widget instance and trigger preselect
        const event = new CustomEvent('bookingstack:preselect', { 
            detail: { preselect: preselectString } 
        });
        window.dispatchEvent(event);
    }
};

// ============================================
// Universal auto-init system
// ============================================
function findWidgetElements() {
    const elements = [];
    
    // Find all pricing widgets
    document.querySelectorAll('[data-bookingstack="pricing"]').forEach(el => {
        elements.push({ el, type: 'pricing' });
    });
    
    // Find booking widget
    const bookingEl = document.querySelector('[data-bookingstack="booking"]') 
        || document.querySelector('[data-bookingstack]:not([data-bookingstack="pricing"])')
        || document.querySelector('#bookingstack-widget');
    
    if (bookingEl && !elements.some(e => e.el === bookingEl)) {
        elements.push({ el: bookingEl, type: 'booking' });
    }

    return elements;
}

function initElement(el, type) {
    if (!el || initialized.has(el)) return false;
    
    const tenant = el.dataset.tenant || (type === 'booking' ? el.dataset.bookingstack : null);
    if (!tenant || tenant === 'pricing') {
        if (!el.dataset.tenant) return false;
    }

    const options = {
        type: type,
        tenant: el.dataset.tenant || el.dataset.bookingstack,
        apiUrl: el.dataset.api || undefined,
        category: el.dataset.category || undefined,
        bookingUrl: el.dataset.bookingUrl || undefined,
        primaryColor: el.dataset.primaryColor || undefined,
    };

    window.BookingStack.init(el, options);
    return true;
}

function tryInit() {
    const elements = findWidgetElements();
    let initCount = 0;
    elements.forEach(({ el, type }) => {
        if (initElement(el, type)) initCount++;
    });
    return initCount > 0;
}

// Strategy 1: Immediate check
if (!tryInit()) {
    // Strategy 2: DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInit);
    }

    // Strategy 3: Full load
    window.addEventListener('load', tryInit);

    // Strategy 4: MutationObserver
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function() {
            tryInit();
            // Don't disconnect — there may be multiple widgets added at different times
        });

        function startObserving() {
            if (document.body) {
                observer.observe(document.body, { childList: true, subtree: true });
                setTimeout(() => observer.disconnect(), 30000);
            } else {
                setTimeout(startObserving, 10);
            }
        }
        startObserving();
    }

    // Strategy 5: Polling fallback
    let pollCount = 0;
    const pollInterval = setInterval(function() {
        pollCount++;
        if (tryInit() || pollCount >= 25) {
            clearInterval(pollInterval);
        }
    }, 200);
}