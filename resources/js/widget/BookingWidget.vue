<template>
  <div class="bs-widget" :style="cssVars">
    <!-- Loading -->
    <div v-if="loading" class="bs-loading">
      <div class="bs-spinner"></div>
      <p>Loading services...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bs-error">
      <p>{{ error }}</p>
      <button @click="loadServices" class="bs-btn bs-btn-primary" style="margin-top: 1rem; width: auto;">
        Try Again
      </button>
    </div>

    <!-- Widget Content -->
    <template v-else>
      <!-- Steps -->
      <div class="bs-steps">
        <div 
          v-for="(stepName, index) in steps" 
          :key="index"
          class="bs-step"
          :class="{ 
            active: currentStep === index, 
            completed: currentStep > index 
          }"
        >
          <span>{{ index + 1 }}</span>
          <span>{{ stepName }}</span>
        </div>
      </div>

      <!-- Step 1: Services -->
      <ServiceSelector
        v-if="currentStep === 0"
        :categories="categories"
        :cart="cart"
        :currency="tenantData.currency"
        @update-cart="updateCart"
      />

      <!-- Step 2: Date & Time -->
      <DateTimePicker
        v-if="currentStep === 1"
        :tenant="tenant"
        :api-url="apiUrl"
        :selected-date="selectedDate"
        :selected-time="selectedTime"
        @select-date="selectedDate = $event"
        @select-time="selectedTime = $event"
      />

      <!-- Step 3: Details -->
      <CheckoutForm
        v-if="currentStep === 2"
        :customer="customer"
        @update-customer="customer = $event"
      />

      <!-- Step 4: Confirmation -->
      <Confirmation
        v-if="currentStep === 3"
        :booking="completedBooking"
      />

      <!-- Cart & Navigation -->
      <div v-if="currentStep < 3" class="bs-cart">
        <!-- Cart items summary -->
        <div v-if="cartItems.length > 0" style="margin-bottom: 1rem; font-size: 0.875rem; color: #6b7280;">
          {{ cartItems.length }} service(s) selected
        </div>

        <!-- Minimum amount warning -->
        <div v-if="tenantData.min_booking_amount && subtotal < tenantData.min_booking_amount" 
             style="color: #dc2626; font-size: 0.875rem; margin-bottom: 0.5rem;">
          Minimum booking amount: {{ formatCurrency(tenantData.min_booking_amount) }}
        </div>

        <!-- Promo Code Section - показываем на шагах 0-2 -->
        <div class="bs-promo-section">
          <!-- Input для ввода промокода (только если ещё не применён) -->
          <div v-if="!promoApplied" class="bs-promo-input">
            <input 
              type="text" 
              v-model="promoCode" 
              placeholder="Promo code"
              class="bs-promo-field"
              @keyup.enter="applyPromoCode"
            >
            <button 
              @click="applyPromoCode" 
              class="bs-promo-btn"
              :disabled="applyingPromo || !promoCode.trim()"
            >
              {{ applyingPromo ? '...' : 'Apply' }}
            </button>
          </div>
          
          <!-- Показываем применённый промокод на всех шагах -->
          <div v-else class="bs-promo-applied">
            <span class="bs-promo-success">
              ✓ {{ promoApplied.code }} (-{{ formatCurrency(promoDiscount) }})
            </span>
            <button @click="removePromoCode" class="bs-promo-remove" title="Remove promo code">×</button>
          </div>
          
          <div v-if="promoError" class="bs-promo-error">{{ promoError }}</div>
        </div>

        <!-- Subtotal & Discount -->
        <div v-if="promoDiscount > 0" class="bs-cart-subtotal">
          <span>Subtotal:</span>
          <span>{{ formatCurrency(subtotal) }}</span>
        </div>
        <div v-if="promoDiscount > 0" class="bs-cart-discount">
          <span>Discount:</span>
          <span>-{{ formatCurrency(promoDiscount) }}</span>
        </div>

        <div class="bs-cart-total">
          <span>Total:</span>
          <span>{{ formatCurrency(total) }}</span>
        </div>

        <div style="display: flex; gap: 0.5rem;">
          <button 
            v-if="currentStep > 0"
            @click="currentStep--" 
            class="bs-btn"
            style="background: #e5e7eb; color: #374151; width: auto; flex: 0;"
          >
            Back
          </button>
          <button 
            @click="nextStep" 
            class="bs-btn bs-btn-primary"
            :disabled="!canProceed || submitting"
          >
            <span v-if="submitting">Processing...</span>
            <span v-else>{{ currentStep === 2 ? 'Complete Booking' : 'Continue' }}</span>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import ServiceSelector from './components/ServiceSelector.vue';
import DateTimePicker from './components/DateTimePicker.vue';
import CheckoutForm from './components/CheckoutForm.vue';
import Confirmation from './components/Confirmation.vue';

export default {
  name: 'BookingWidget',
  
  components: {
    ServiceSelector,
    DateTimePicker,
    CheckoutForm,
    Confirmation,
  },

  props: {
    tenant: { type: String, required: true },
    apiUrl: { type: String, default: '/api/v1' },
    primaryColor: { type: String, default: null },
  },

  setup(props) {
    const loading = ref(true);
    const error = ref(null);
    const tenantData = ref({});
    const categories = ref([]);
    const cart = ref({});
    const currentStep = ref(0);
    const selectedDate = ref(null);
    const selectedTime = ref(null);
    const customer = ref({
      name: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      state: '',
      zip: '',
      message: '',
    });
    const completedBooking = ref(null);
    const submitting = ref(false);
    const promoCode = ref('');
    const promoDiscount = ref(0);
    const promoError = ref('');
    const promoApplied = ref(null);
    const applyingPromo = ref(false);

    const steps = ['Services', 'Date & Time', 'Details', 'Confirmation'];

    // Computed
    const cartItems = computed(() => {
      const items = [];
      for (const [serviceId, qty] of Object.entries(cart.value)) {
        if (qty > 0) {
          const service = findService(serviceId);
          if (service) {
            items.push({ ...service, quantity: qty });
          }
        }
      }
      return items;
    });

    const subtotal = computed(() => {
      return cartItems.value.reduce((sum, item) => {
        return sum + (parseFloat(item.price) * item.quantity);
      }, 0);
    });

    const total = computed(() => {
      return Math.max(0, subtotal.value - promoDiscount.value);
    });

    const canProceed = computed(() => {
      if (currentStep.value === 0) {
        const minAmount = tenantData.value.min_booking_amount || 0;
        return cartItems.value.length > 0 && subtotal.value >= minAmount;
      }
      if (currentStep.value === 1) {
        return selectedDate.value && selectedTime.value?.start;
      }
      if (currentStep.value === 2) {
        return customer.value.name && customer.value.email && 
               customer.value.phone && customer.value.address;
      }
      return true;
    });

    const cssVars = computed(() => {
      const color = props.primaryColor || tenantData.value.primary_color || '#10b981';
      return { '--bs-primary': color };
    });

    // Methods
    function findService(serviceId) {
      for (const cat of categories.value) {
        const service = cat.services?.find(s => String(s.id) === String(serviceId));
        if (service) return service;
      }
      return null;
    }

    function formatCurrency(amount) {
      const currency = tenantData.value.currency || 'USD';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
      }).format(amount);
    }

    function updateCart(serviceId, quantity) {
      cart.value = { ...cart.value, [serviceId]: quantity };
      
      // Пересчитать скидку при изменении корзины
      if (promoApplied.value) {
        recalculateDiscount();
      }
      
      // GA4: add_to_cart event
      if (quantity > 0 && window.dataLayer) {
        const service = findService(serviceId);
        if (service) {
          window.dataLayer.push({
            event: 'add_to_cart',
            ecommerce: {
              currency: tenantData.value.currency || 'USD',
              value: parseFloat(service.price) * quantity,
              items: [{
                item_id: serviceId,
                item_name: service.name,
                item_category: service.category_name,
                price: parseFloat(service.price),
                quantity: quantity,
              }]
            }
          });
        }
      }
    }

    async function recalculateDiscount() {
      if (!promoApplied.value) return;
      
      try {
        const response = await fetch(`${props.apiUrl}/${props.tenant}/promo/validate`, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            code: promoApplied.value.code,
            subtotal: subtotal.value,
            items: cartItems.value.map(item => ({
              service_id: item.id,
              total_price: parseFloat(item.price) * item.quantity,
            })),
          }),
        });
        
        const data = await response.json();
        
        if (data.valid) {
          promoDiscount.value = data.discount_amount;
        }
      } catch (e) {
        // Silently fail, keep old discount
      }
    }

    async function loadServices() {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await fetch(`${props.apiUrl}/${props.tenant}/services`);
        if (!response.ok) throw new Error('Failed to load services');
        
        const data = await response.json();
        tenantData.value = data.tenant || {};
        categories.value = data.categories || [];

        // GA4: widget loaded
        if (window.dataLayer) {
          window.dataLayer.push({
            event: 'bookingstack_widget_loaded',
            tenant_id: props.tenant,
          });
        }
      } catch (e) {
        error.value = e.message || 'Failed to load services';
      } finally {
        loading.value = false;
      }
    }

    async function nextStep() {
      if (!canProceed.value) return;

      // GA4: begin_checkout when moving to step 2
      if (currentStep.value === 0 && window.dataLayer) {
        window.dataLayer.push({
          event: 'begin_checkout',
          ecommerce: {
            currency: tenantData.value.currency || 'USD',
            value: subtotal.value,
            items: cartItems.value.map(item => ({
              item_id: item.id,
              item_name: item.name,
              price: parseFloat(item.price),
              quantity: item.quantity,
            }))
          }
        });
      }

      // Submit booking on step 3
      if (currentStep.value === 2) {
        await submitBooking();
        return;
      }

      currentStep.value++;
    }

    async function submitBooking() {
      if (submitting.value) return;
      submitting.value = true;

      try {
        const payload = {
          customer_name: customer.value.name,
          customer_email: customer.value.email,
          customer_phone: customer.value.phone,
          address: customer.value.address,
          city: customer.value.city,
          state: customer.value.state,
          zip: customer.value.zip,
          message: customer.value.message,
          scheduled_date: selectedDate.value,
          scheduled_time_start: selectedTime.value?.start,
          scheduled_time_end: selectedTime.value?.end,
          items: cartItems.value.map(item => ({
            service_id: item.id,
            quantity: item.quantity,
          })),
          promo_code: promoApplied.value?.code || null,
          // UTM tracking
          utm_source: getUrlParam('utm_source'),
          utm_medium: getUrlParam('utm_medium'),
          utm_campaign: getUrlParam('utm_campaign'),
        };

        const response = await fetch(`${props.apiUrl}/${props.tenant}/bookings`, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });

        if (!response.ok) {
          const err = await response.json();
          throw new Error(err.message || 'Booking failed');
        }

        const data = await response.json();
        
        // Сохраняем booking с добавлением customer данных (на случай если backend не возвращает)
        completedBooking.value = {
          ...data.booking,
          // Fallback на локальные данные если backend не вернул
          address: data.booking.address || customer.value.address,
          city: data.booking.city || customer.value.city,
          state: data.booking.state || customer.value.state,
          zip: data.booking.zip || customer.value.zip,
          customer_email: data.booking.customer_email || customer.value.email,
        };
        
        currentStep.value = 3;

        // GA4: purchase event
        if (window.dataLayer && completedBooking.value) {
          window.dataLayer.push({
            event: 'purchase',
            ecommerce: {
              transaction_id: completedBooking.value.reference_number,
              currency: tenantData.value.currency || 'USD',
              value: completedBooking.value.total,
              coupon: promoApplied.value?.code || undefined,
              items: cartItems.value.map(item => ({
                item_id: item.id,
                item_name: item.name,
                price: parseFloat(item.price),
                quantity: item.quantity,
              }))
            }
          });
        }
      } catch (e) {
        alert(e.message || 'Failed to create booking');
      } finally {
        submitting.value = false;
      }
    }

    async function applyPromoCode() {
      if (!promoCode.value.trim()) {
        promoError.value = 'Enter a promo code';
        return;
      }
      
      applyingPromo.value = true;
      promoError.value = '';
      
      try {
        const response = await fetch(`${props.apiUrl}/${props.tenant}/promo/validate`, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            code: promoCode.value,
            subtotal: subtotal.value,
            items: cartItems.value.map(item => ({
              service_id: item.id,
              total_price: parseFloat(item.price) * item.quantity,
            })),
          }),
        });
        
        const data = await response.json();
        
        if (data.valid) {
          promoDiscount.value = data.discount_amount;
          promoApplied.value = data;
          promoError.value = '';
          
          if (window.dataLayer) {
            window.dataLayer.push({
              event: 'bookingstack_promo_applied',
              promo_code: promoCode.value,
              discount_amount: data.discount_amount,
            });
          }
        } else {
          promoError.value = data.message || 'Invalid promo code';
          promoDiscount.value = 0;
          promoApplied.value = null;
        }
      } catch (e) {
        promoError.value = 'Failed to validate promo code';
        promoDiscount.value = 0;
        promoApplied.value = null;
      } finally {
        applyingPromo.value = false;
      }
    }

    function removePromoCode() {
      promoCode.value = '';
      promoDiscount.value = 0;
      promoApplied.value = null;
      promoError.value = '';
    }

    function getUrlParam(param) {
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get(param) || '';
    }

    onMounted(() => {
      loadServices();
    });

    return {
      loading,
      error,
      tenantData,
      categories,
      cart,
      currentStep,
      selectedDate,
      selectedTime,
      customer,
      completedBooking,
      submitting,
      steps,
      cartItems,
      subtotal,
      total,
      canProceed,
      cssVars,
      formatCurrency,
      updateCart,
      loadServices,
      nextStep,
      promoCode,
      promoDiscount,
      promoError,
      promoApplied,
      applyingPromo,
      applyPromoCode,
      removePromoCode,
    };
  },
};
</script>
