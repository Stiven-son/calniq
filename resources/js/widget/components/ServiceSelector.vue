<template>
  <div class="bs-service-selector">
    <!-- Category Quick Nav -->
    <div v-if="categories.length > 1" class="bs-category-nav">
      <button 
        class="bs-category-nav-arrow bs-category-nav-arrow-left"
        @click="scrollNav('left')"
        :style="{ visibility: canScrollLeft ? 'visible' : 'hidden' }"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <div class="bs-category-nav-scroll" ref="navScroll" @scroll="updateNavArrows">
        <button
          v-for="category in categories"
          :key="'nav-' + category.id"
          class="bs-category-nav-item"
          :class="{ active: activeCategory === category.id }"
          @click="scrollToCategory(category.id)"
        >
          <img 
            v-if="getCategoryIcon(category)" 
            :src="getCategoryIcon(category)" 
            :alt="category.name"
            class="bs-category-nav-icon"
          >
          <span class="bs-category-nav-label">{{ category.name }}</span>
        </button>
      </div>
      <button 
        class="bs-category-nav-arrow bs-category-nav-arrow-right"
        @click="scrollNav('right')"
        :style="{ visibility: canScrollRight ? 'visible' : 'hidden' }"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    </div>

    <!-- Search & View Toggle -->
    <div class="bs-service-controls">
      <div class="bs-search-wrapper">
        <svg class="bs-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.3-4.3"/>
        </svg>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search services..."
          class="bs-search-input"
        >
        <button 
          v-if="searchQuery" 
          @click="searchQuery = ''" 
          class="bs-search-clear"
        >×</button>
      </div>
      
      <div class="bs-view-toggle">
        <button 
          @click="viewMode = 'grid'" 
          class="bs-view-btn"
          :class="{ active: viewMode === 'grid' }"
          title="Grid view"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <rect x="3" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/>
          </svg>
        </button>
        <button 
          @click="viewMode = 'list'" 
          class="bs-view-btn"
          :class="{ active: viewMode === 'list' }"
          title="List view"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <rect x="3" y="4" width="18" height="4" rx="1"/>
            <rect x="3" y="10" width="18" height="4" rx="1"/>
            <rect x="3" y="16" width="18" height="4" rx="1"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- No results message -->
    <div v-if="filteredCategories.length === 0 && searchQuery" class="bs-no-results">
      <p>No services found for "{{ searchQuery }}"</p>
      <button @click="searchQuery = ''" class="bs-clear-search-btn">Clear search</button>
    </div>

    <!-- Categories & Services -->
    <div 
      v-for="category in filteredCategories" 
      :key="category.id" 
      class="bs-category"
      :ref="el => setCategoryRef(category.id, el)"
    >
      <h3 class="bs-category-title">{{ category.name }}</h3>
      <p v-if="category.description" class="bs-category-desc">{{ category.description }}</p>
      
      <!-- Grid View -->
      <div v-if="viewMode === 'grid'" class="bs-services-grid">
        <div
          v-for="service in category.filteredServices"
          :key="service.id"
          class="bs-service-card"
          :class="{ selected: getQuantity(service.id) > 0 }"
          @click="toggleService(service)"
        >
          <img 
            v-if="service.image_full_url" 
            :src="service.image_full_url" 
            :alt="service.name"
            class="bs-service-image"
          >
          <div v-else class="bs-service-image-placeholder">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <path d="m21 15-5-5L5 21"/>
            </svg>
          </div>
          
          <div class="bs-service-name">{{ service.name }}</div>
          
          <div v-if="service.description" class="bs-service-desc">
            {{ service.description }}
          </div>
          
          <div class="bs-service-price">
            {{ formatPrice(service.price) }}
            <span v-if="service.price_type !== 'fixed'" class="bs-price-unit">
              / {{ service.price_unit || 'unit' }}
            </span>
          </div>

          <!-- Quantity controls -->
          <div v-if="getQuantity(service.id) > 0" class="bs-quantity" @click.stop>
            <button class="bs-qty-btn" @click="decrementQuantity(service)">−</button>
            <span class="bs-qty-value">{{ getQuantity(service.id) }}</span>
            <button 
              class="bs-qty-btn" 
              @click="incrementQuantity(service)"
              :disabled="getQuantity(service.id) >= (service.max_quantity || 10)"
            >+</button>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-else class="bs-services-list">
        <div
          v-for="service in category.filteredServices"
          :key="service.id"
          class="bs-service-row"
          :class="{ selected: getQuantity(service.id) > 0 }"
          @click="toggleService(service)"
        >
          <img 
            v-if="service.image_full_url" 
            :src="service.image_full_url"
            :alt="service.name"
            class="bs-service-thumb"
          >
          <div v-else class="bs-service-thumb-placeholder">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <path d="m21 15-5-5L5 21"/>
            </svg>
          </div>
          
          <div class="bs-service-info">
            <div class="bs-service-name">{{ service.name }}</div>
            <div v-if="service.description" class="bs-service-desc">{{ service.description }}</div>
          </div>
          
          <div class="bs-service-price">
            {{ formatPrice(service.price) }}
            <span v-if="service.price_type !== 'fixed'" class="bs-price-unit">
              / {{ service.price_unit || 'unit' }}
            </span>
          </div>

          <!-- Quantity controls for list -->
          <div v-if="getQuantity(service.id) > 0" class="bs-quantity" @click.stop>
            <button class="bs-qty-btn" @click="decrementQuantity(service)">−</button>
            <span class="bs-qty-value">{{ getQuantity(service.id) }}</span>
            <button 
              class="bs-qty-btn" 
              @click="incrementQuantity(service)"
              :disabled="getQuantity(service.id) >= (service.max_quantity || 10)"
            >+</button>
          </div>
          <div v-else class="bs-add-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14M5 12h14"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div v-if="categories.length === 0" class="bs-no-services">
      No services available
    </div>
  </div>
</template>

<script>
import { ref, nextTick, onMounted } from 'vue';

export default {
  name: 'ServiceSelector',

  props: {
    categories: { type: Array, default: () => [] },
    cart: { type: Object, default: () => ({}) },
    currency: { type: String, default: 'USD' },
    apiBaseUrl: { type: String, default: '' },
  },

  emits: ['update-cart'],

  setup(props) {
    const navScroll = ref(null);
    const canScrollLeft = ref(false);
    const canScrollRight = ref(false);
    const activeCategory = ref(null);
    const categoryRefs = {};

    function setCategoryRef(id, el) {
      if (el) categoryRefs[id] = el;
    }

    function updateNavArrows() {
      if (!navScroll.value) return;
      const el = navScroll.value;
      canScrollLeft.value = el.scrollLeft > 5;
      canScrollRight.value = el.scrollLeft < (el.scrollWidth - el.clientWidth - 5);
    }

    function scrollNav(direction) {
      if (!navScroll.value) return;
      const amount = 200;
      navScroll.value.scrollBy({ 
        left: direction === 'left' ? -amount : amount, 
        behavior: 'smooth' 
      });
    }

    function scrollToCategory(categoryId) {
      activeCategory.value = categoryId;
      const el = categoryRefs[categoryId];
      if (!el) return;
      window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
    }

    function getCategoryIcon(category) {
      if (!category.icon_url) return null;
      // If already absolute URL, return as-is
      if (category.icon_url.startsWith('http')) return category.icon_url;
      // Build absolute URL from API base
      if (props.apiBaseUrl) {
        try {
          const origin = new URL(props.apiBaseUrl).origin;
          return origin + category.icon_url;
        } catch(e) {}
      }
      return category.icon_url;
    }

    onMounted(() => {
      nextTick(() => {
        updateNavArrows();
        if (props.categories.length > 0) {
          activeCategory.value = props.categories[0].id;
        }
      });
    });

    return {
      navScroll,
      canScrollLeft,
      canScrollRight,
      activeCategory,
      setCategoryRef,
      updateNavArrows,
      scrollNav,
      scrollToCategory,
      getCategoryIcon,
    };
  },

  data() {
    return {
      searchQuery: '',
      viewMode: 'grid',
    };
  },

  computed: {
    filteredCategories() {
      if (!this.searchQuery.trim()) {
        return this.categories.map(cat => ({
          ...cat,
          filteredServices: cat.services || []
        }));
      }

      const query = this.searchQuery.toLowerCase().trim();
      
      return this.categories
        .map(category => {
          const filteredServices = (category.services || []).filter(service => {
            return (
              service.name.toLowerCase().includes(query) ||
              (service.description && service.description.toLowerCase().includes(query)) ||
              category.name.toLowerCase().includes(query)
            );
          });
          
          return {
            ...category,
            filteredServices,
          };
        })
        .filter(category => category.filteredServices.length > 0);
    },
  },

  methods: {
    getQuantity(serviceId) {
      return this.cart[serviceId] || 0;
    },

    formatPrice(price) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: this.currency,
      }).format(price);
    },

    toggleService(service) {
      const currentQty = this.getQuantity(service.id);
      if (currentQty === 0) {
        this.$emit('update-cart', service.id, service.min_quantity || 1);
      }
    },

    incrementQuantity(service) {
      const currentQty = this.getQuantity(service.id);
      const maxQty = service.max_quantity || 10;
      if (currentQty < maxQty) {
        this.$emit('update-cart', service.id, currentQty + 1);
      }
    },

    decrementQuantity(service) {
      const currentQty = this.getQuantity(service.id);
      if (currentQty > 0) {
        this.$emit('update-cart', service.id, currentQty - 1);
      }
    },
  },
};
</script>

<style scoped>
/* === Category Quick Nav === */
.bs-category-nav {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-bottom: 1rem;
  position: relative;
}

.bs-category-nav-arrow {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1px solid #e5e7eb;
  background: white !important;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280 !important;
  padding: 0;
  transition: all 0.15s;
}

.bs-category-nav-arrow:hover {
  background: #f3f4f6 !important;
  border-color: #d1d5db;
}

.bs-category-nav-scroll {
  display: flex;
  gap: 0.375rem;
  overflow-x: auto;
  scroll-behavior: smooth;
  flex: 1;
  -ms-overflow-style: none;
  scrollbar-width: none;
  padding: 0.25rem 0;
}

.bs-category-nav-scroll::-webkit-scrollbar {
  display: none;
}

.bs-category-nav-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 9999px;
  border: 1px solid #e5e7eb;
  background: white !important;
  cursor: pointer;
  white-space: nowrap;
  font-size: 0.8rem;
  font-weight: 500;
  color: #6b7280 !important;
  transition: all 0.15s;
  flex-shrink: 0;
}

.bs-category-nav-item:hover {
  border-color: var(--bs-primary, #10b981);
  color: var(--bs-primary, #10b981) !important;
}

.bs-category-nav-item.active {
  background: var(--bs-primary, #10b981) !important;
  border-color: var(--bs-primary, #10b981);
  color: white !important;
}

.bs-category-nav-icon {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.bs-category-nav-label {
  line-height: 1;
}

/* Mobile: compact nav */
@media (max-width: 640px) {
  .bs-category-nav-item {
    padding: 0.3rem 0.5rem;
    font-size: 0.7rem;
    gap: 0.25rem;
  }
  .bs-category-nav-icon {
    width: 18px;
    height: 18px;
  }
  .bs-category-nav-arrow {
    width: 24px;
    height: 24px;
  }
}

/* === Existing scoped styles === */
.bs-service-controls {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  align-items: center;
}

.bs-search-wrapper {
  flex: 1;
  position: relative;
}

.bs-search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.bs-search-input {
  width: 100%;
  padding: 0.625rem 2.5rem 0.625rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

.bs-search-input:focus {
  outline: none;
  border-color: var(--bs-primary, #10b981);
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.bs-search-clear {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
}

.bs-search-clear:hover {
  color: #6b7280;
}

.bs-view-toggle {
  display: flex;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
}

.bs-view-btn {
  padding: 0.5rem 0.75rem;
  background: white;
  border: none;
  cursor: pointer;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bs-view-btn:first-child {
  border-right: 1px solid #d1d5db;
}

.bs-view-btn:hover {
  background: #f9fafb;
}

.bs-view-btn.active {
  background: var(--bs-primary, #10b981);
  color: white;
}

.bs-no-results {
  text-align: center;
  padding: 3rem 1rem;
  color: #6b7280;
}

.bs-clear-search-btn {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  cursor: pointer;
}

.bs-clear-search-btn:hover {
  background: #e5e7eb;
}

.bs-category {
  margin-bottom: 2rem;
}

.bs-category-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #374151;
}

.bs-category-desc {
  color: #6b7280;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.bs-services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.bs-service-card {
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.bs-service-card:hover {
  border-color: var(--bs-primary, #10b981);
}

.bs-service-card.selected {
  border-color: var(--bs-primary, #10b981);
  background: #ecfdf5;
}

.bs-service-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 0.5rem;
  margin-bottom: 0.75rem;
}

.bs-service-image-placeholder {
  width: 100%;
  height: 120px;
  background: #f3f4f6;
  border-radius: 0.5rem;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.bs-services-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.bs-service-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.bs-service-row:hover {
  border-color: var(--bs-primary, #10b981);
}

.bs-service-row.selected {
  border-color: var(--bs-primary, #10b981);
  background: #ecfdf5;
}

.bs-service-thumb {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 0.375rem;
  flex-shrink: 0;
}

.bs-service-thumb-placeholder {
  width: 48px;
  height: 48px;
  background: #f3f4f6;
  border-radius: 0.375rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  flex-shrink: 0;
}

.bs-service-info {
  flex: 1;
  min-width: 0;
}

.bs-service-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.bs-service-desc {
  font-size: 0.75rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.bs-service-price {
  color: var(--bs-primary, #10b981);
  font-weight: 700;
  font-size: 1.125rem;
  white-space: nowrap;
}

.bs-price-unit {
  font-size: 0.75rem;
  font-weight: 400;
}

.bs-add-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  flex-shrink: 0;
}

.bs-service-row:hover .bs-add-btn {
  background: var(--bs-primary, #10b981);
  color: white;
}

.bs-quantity {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.bs-service-row .bs-quantity {
  margin-top: 0;
}

.bs-qty-btn {
  width: 2rem;
  height: 2rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: white;
  cursor: pointer;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bs-qty-btn:hover {
  background: #f3f4f6;
}

.bs-qty-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.bs-qty-value {
  min-width: 2rem;
  text-align: center;
  font-weight: 600;
}

.bs-no-services {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}
</style>