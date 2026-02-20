<template>
  <div class="bs-service-selector">
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
    <div v-for="category in filteredCategories" :key="category.id" class="bs-category">
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
export default {
  name: 'ServiceSelector',

  props: {
    categories: { type: Array, default: () => [] },
    cart: { type: Object, default: () => ({}) },
    currency: { type: String, default: 'USD' },
  },

  emits: ['update-cart'],

  data() {
    return {
      searchQuery: '',
      viewMode: 'grid', // 'grid' or 'list'
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

/* Grid View Styles */
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

/* List View Styles */
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

/* Quantity controls */
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
