<template>
  <div class="bs-pricing" :style="cssVars">
    <!-- Loading -->
    <div v-if="loading" class="bs-loading">
      <div class="bs-spinner"></div>
      <p>Loading services...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bs-pricing-error">
      <p>{{ error }}</p>
      <button @click="loadCategory" class="bs-pricing-retry-btn">Try Again</button>
    </div>

    <!-- Content -->
    <template v-else>
      <!-- Category Title -->
      <h2 v-if="categoryName" class="bs-pricing-title">{{ categoryName }}</h2>

      <!-- Services Grid -->
      <div class="bs-pricing-grid">
        <div
          v-for="service in services"
          :key="service.id"
          class="bs-pricing-card"
          :class="{ selected: getQuantity(service.id) > 0 }"
          @click="toggleService(service)"
        >
          <img 
            v-if="service.image_full_url" 
            :src="service.image_full_url" 
            :alt="service.name"
            class="bs-pricing-card-image"
          >
          <div v-else class="bs-pricing-card-image-placeholder">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <path d="m21 15-5-5L5 21"/>
            </svg>
          </div>

          <div class="bs-pricing-card-body">
            <div class="bs-pricing-card-name">{{ service.name }}</div>
            <div v-if="service.description" class="bs-pricing-card-desc">{{ service.description }}</div>
            <div class="bs-pricing-card-price">
              {{ formatPrice(service.price) }}
              <span v-if="service.price_type !== 'fixed'" class="bs-pricing-card-unit">
                / {{ service.price_unit || 'unit' }}
              </span>
            </div>
          </div>

          <!-- Quantity controls (shown when selected) -->
          <div v-if="getQuantity(service.id) > 0" class="bs-pricing-qty" @click.stop>
            <button class="bs-pricing-qty-btn" @click="decrementQuantity(service)">−</button>
            <span class="bs-pricing-qty-value">{{ getQuantity(service.id) }}</span>
            <button 
              class="bs-pricing-qty-btn" 
              @click="incrementQuantity(service)"
              :disabled="getQuantity(service.id) >= (service.max_quantity || 10)"
            >+</button>
          </div>
        </div>
      </div>

      <!-- Sticky Bottom Bar -->
      <div v-if="cartItems.length > 0" class="bs-pricing-bar">
        <div class="bs-pricing-bar-summary">
          <span class="bs-pricing-bar-items">
            {{ cartItems.map(i => i.name + ' ×' + i.quantity).join(', ') }}
          </span>
          <span class="bs-pricing-bar-total">
            Total: {{ formatPrice(cartTotal) }}
          </span>
        </div>
        <button class="bs-pricing-bar-btn" @click="proceedToBooking">
          Proceed to Booking →
        </button>
      </div>
    </template>

    <!-- Schema.org JSON-LD -->
    <component :is="'script'" type="application/ld+json" v-if="schemaData">
      {{ schemaData }}
    </component>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

export default {
  name: 'PricingWidget',

  props: {
    tenant: { type: String, required: true },
    category: { type: String, required: true },
    apiUrl: { type: String, default: '/api/v1' },
    bookingUrl: { type: String, default: '' },
    primaryColor: { type: String, default: null },
  },

  setup(props) {
    const loading = ref(true);
    const error = ref(null);
    const categoryName = ref('');
    const services = ref([]);
    const cart = ref({});
    const tenantData = ref({});

    const cssVars = computed(() => {
      const color = props.primaryColor || tenantData.value.primary_color || '#10b981';
      return { '--bs-primary': color };
    });

    const cartItems = computed(() => {
      const items = [];
      for (const [serviceId, qty] of Object.entries(cart.value)) {
        if (qty > 0) {
          const service = services.value.find(s => String(s.id) === String(serviceId));
          if (service) {
            items.push({ ...service, quantity: qty });
          }
        }
      }
      return items;
    });

    const cartTotal = computed(() => {
      return cartItems.value.reduce((sum, item) => {
        return sum + (parseFloat(item.price) * item.quantity);
      }, 0);
    });

    const schemaData = computed(() => {
      if (!services.value.length || !categoryName.value) return null;
      const schema = {
        "@context": "https://schema.org",
        "@type": "Service",
        "provider": {
          "@type": "LocalBusiness",
          "name": tenantData.value.name || props.tenant
        },
        "name": categoryName.value,
        "hasOfferCatalog": {
          "@type": "OfferCatalog",
          "name": categoryName.value + " Services",
          "itemListElement": services.value.map(s => ({
            "@type": "Offer",
            "itemOffered": {
              "@type": "Service",
              "name": s.name,
              "description": s.description || undefined
            },
            "price": String(s.price),
            "priceCurrency": tenantData.value.currency || "USD"
          }))
        }
      };
      return JSON.stringify(schema);
    });

    function formatPrice(price) {
      const currency = tenantData.value.currency || 'USD';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
      }).format(price);
    }

    function getQuantity(serviceId) {
      return cart.value[serviceId] || 0;
    }

    function toggleService(service) {
      if (getQuantity(service.id) === 0) {
        cart.value = { ...cart.value, [service.id]: service.min_quantity || 1 };
      }
    }

    function incrementQuantity(service) {
      const current = getQuantity(service.id);
      const max = service.max_quantity || 10;
      if (current < max) {
        cart.value = { ...cart.value, [service.id]: current + 1 };
      }
    }

    function decrementQuantity(service) {
      const current = getQuantity(service.id);
      if (current > 0) {
        cart.value = { ...cart.value, [service.id]: current - 1 };
      }
    }

    function proceedToBooking() {
      if (cartItems.value.length === 0) return;

      // Build preselect string: serviceId:qty,serviceId:qty
      const preselect = cartItems.value
        .map(item => item.id + ':' + item.quantity)
        .join(',');

      // Determine booking URL
      let url = props.bookingUrl;

      if (!url) {
        // Try to find booking widget on same page
        const bookingEl = document.querySelector('[data-bookingstack="booking"]') 
          || document.querySelector('#bookingstack-widget');
        
        if (bookingEl) {
          // Booking widget exists on same page — trigger it with preselect
          if (window.BookingStack && window.BookingStack.preselect) {
            window.BookingStack.preselect(preselect);
            return;
          }
        }

        // Default: current page with preselect param
        url = window.location.href.split('?')[0] + '?preselect=' + preselect;
      } else {
        // Append preselect to provided URL
        const separator = url.includes('?') ? '&' : '?';
        url = url + separator + 'preselect=' + preselect;
      }

      window.location.href = url;
    }

    async function loadCategory() {
      loading.value = true;
      error.value = null;

      try {
        const response = await fetch(`${props.apiUrl}/${props.tenant}/pricing/${props.category}`);
        if (!response.ok) {
          if (response.status === 404) {
            throw new Error('Category not found');
          }
          throw new Error('Failed to load services');
        }

        const data = await response.json();
        tenantData.value = data.tenant || {};
        categoryName.value = data.category?.name || '';
        services.value = data.services || [];
      } catch (e) {
        error.value = e.message || 'Failed to load services';
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      loadCategory();
    });

    return {
      loading,
      error,
      categoryName,
      services,
      cart,
      tenantData,
      cssVars,
      cartItems,
      cartTotal,
      schemaData,
      formatPrice,
      getQuantity,
      toggleService,
      incrementQuantity,
      decrementQuantity,
      proceedToBooking,
      loadCategory,
    };
  },
};
</script>

<style scoped>
.bs-pricing {
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  max-width: 900px;
  margin: 0 auto;
  padding-bottom: 80px; /* space for sticky bar */
  color: #374151;
}

.bs-pricing *, .bs-pricing *::before, .bs-pricing *::after {
  box-sizing: border-box;
}

/* Loading */
.bs-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3rem;
  color: #6b7280;
}

.bs-spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e5e7eb;
  border-top-color: var(--bs-primary, #10b981);
  border-radius: 50%;
  animation: bs-spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

@keyframes bs-spin {
  to { transform: rotate(360deg); }
}

/* Error */
.bs-pricing-error {
  text-align: center;
  padding: 3rem 1rem;
  color: #6b7280;
}

.bs-pricing-retry-btn {
  margin-top: 1rem;
  padding: 0.5rem 1.5rem;
  background: var(--bs-primary, #10b981);
  color: white !important;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
}

.bs-pricing-retry-btn:hover {
  opacity: 0.9;
}

/* Title */
.bs-pricing-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 0 0 1.5rem 0;
}

/* Grid */
.bs-pricing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

/* Card */
.bs-pricing-card {
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
  position: relative;
}

.bs-pricing-card:hover {
  border-color: var(--bs-primary, #10b981);
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.bs-pricing-card.selected {
  border-color: var(--bs-primary, #10b981);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.03), rgba(16, 185, 129, 0.08));
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.bs-pricing-card-image {
  width: 100%;
  height: 140px;
  object-fit: cover;
}

.bs-pricing-card-image-placeholder {
  width: 100%;
  height: 140px;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.bs-pricing-card-body {
  padding: 0.875rem 1rem;
}

.bs-pricing-card-name {
  font-weight: 600;
  font-size: 0.95rem;
  color: #111827;
  margin-bottom: 0.25rem;
}

.bs-pricing-card-desc {
  font-size: 0.8rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.bs-pricing-card-price {
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--bs-primary, #10b981);
}

.bs-pricing-card-unit {
  font-size: 0.75rem;
  font-weight: 400;
  color: #6b7280;
}

/* Quantity */
.bs-pricing-qty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: rgba(16, 185, 129, 0.05);
  border-top: 1px solid rgba(16, 185, 129, 0.15);
}

.bs-pricing-qty-btn {
  width: 2.25rem;
  height: 2.25rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: white !important;
  cursor: pointer;
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #374151 !important;
  transition: all 0.15s;
}

.bs-pricing-qty-btn:hover {
  background: #f3f4f6 !important;
  border-color: var(--bs-primary, #10b981);
}

.bs-pricing-qty-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.bs-pricing-qty-value {
  min-width: 2rem;
  text-align: center;
  font-weight: 700;
  font-size: 1.1rem;
  color: #111827;
}

/* Sticky Bottom Bar */
.bs-pricing-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-top: 2px solid #e5e7eb;
  padding: 0.75rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  z-index: 9999;
  box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
}

.bs-pricing-bar-summary {
  flex: 1;
  min-width: 0;
}

.bs-pricing-bar-items {
  display: block;
  font-size: 0.8rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.bs-pricing-bar-total {
  display: block;
  font-weight: 700;
  font-size: 1.1rem;
  color: #111827;
  margin-top: 0.125rem;
}

.bs-pricing-bar-btn {
  flex-shrink: 0;
  padding: 0.75rem 1.5rem;
  background: var(--bs-primary, #10b981) !important;
  color: white !important;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s;
}

.bs-pricing-bar-btn:hover {
  opacity: 0.9;
}

/* Responsive */
@media (max-width: 640px) {
  .bs-pricing-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.75rem;
  }

  .bs-pricing-card-image,
  .bs-pricing-card-image-placeholder {
    height: 110px;
  }

  .bs-pricing-card-body {
    padding: 0.625rem 0.75rem;
  }

  .bs-pricing-card-name {
    font-size: 0.85rem;
  }

  .bs-pricing-card-price {
    font-size: 1.1rem;
  }

  .bs-pricing-bar {
    flex-direction: column;
    padding: 0.75rem 1rem;
  }

  .bs-pricing-bar-summary {
    width: 100%;
    text-align: center;
  }

  .bs-pricing-bar-btn {
    width: 100%;
  }

  .bs-pricing-title {
    font-size: 1.25rem;
  }
}
</style>
