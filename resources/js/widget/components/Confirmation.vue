<template>
  <div class="bs-confirmation">
    <div class="bs-success-icon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
        <polyline points="22 4 12 14.01 9 11.01" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>

    <h2 style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-bottom: 0.5rem;">
      Booking Confirmed!
    </h2>

    <p style="color: #6b7280; margin-bottom: 2rem;">
      Thank you for your booking. We'll contact you shortly to confirm.
    </p>

    <div class="bs-booking-details">
      <div class="bs-detail-row">
        <span class="bs-detail-label">Reference Number</span>
        <span class="bs-detail-value" style="font-weight: 700; color: #059669;">
          {{ booking.reference_number }}
        </span>
      </div>

      <div class="bs-detail-row">
        <span class="bs-detail-label">Date & Time</span>
        <span class="bs-detail-value">
          {{ formatDate(booking.scheduled_date) }} at {{ formatTime(booking.scheduled_time_start) }}
        </span>
      </div>

      <div class="bs-detail-row">
        <span class="bs-detail-label">Address</span>
        <span class="bs-detail-value">
          {{ formattedAddress }}
        </span>
      </div>

      <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 1rem 0;">

      <div v-if="booking.items && booking.items.length > 0">
        <div v-for="item in booking.items" :key="item.id" class="bs-detail-row">
          <span class="bs-detail-label">{{ item.service_name }} × {{ item.quantity }}</span>
          <span class="bs-detail-value">{{ formatCurrency(item.total_price) }}</span>
        </div>
      </div>

      <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 1rem 0;">

      <div v-if="booking.discount_amount > 0" class="bs-detail-row">
        <span class="bs-detail-label">Discount</span>
        <span class="bs-detail-value" style="color: #059669;">
          -{{ formatCurrency(booking.discount_amount) }}
        </span>
      </div>

      <div class="bs-detail-row" style="font-size: 1.125rem; font-weight: 700;">
        <span>Total</span>
        <span>{{ formatCurrency(booking.total) }}</span>
      </div>
    </div>

    <p style="font-size: 0.875rem; color: #6b7280; margin-top: 1.5rem; text-align: center;">
      A confirmation email has been sent to <strong>{{ booking.customer_email }}</strong>
    </p>
  </div>
</template>

<script>
export default {
  name: 'Confirmation',

  props: {
    booking: {
      type: Object,
      default: () => ({}),
    },
  },

  computed: {
    formattedAddress() {
      const parts = [];
      
      // Street address
      if (this.booking.address) {
        let street = this.booking.address;
        if (this.booking.address_unit) {
          street += `, ${this.booking.address_unit}`;
        }
        parts.push(street);
      }
      
      // City, State ZIP
      const cityStateZip = [];
      if (this.booking.city) {
        cityStateZip.push(this.booking.city);
      }
      if (this.booking.state) {
        cityStateZip.push(this.booking.state);
      }
      
      if (cityStateZip.length > 0) {
        let location = cityStateZip.join(', ');
        if (this.booking.zip) {
          location += ` ${this.booking.zip}`;
        }
        parts.push(location);
      } else if (this.booking.zip) {
        parts.push(this.booking.zip);
      }
      
      return parts.join(', ');
    },
  },

  methods: {
    formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr + 'T00:00:00');
      return date.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      });
    },

    formatTime(timeStr) {
      if (!timeStr) return '';
      const [hours, minutes] = timeStr.split(':');
      const hour = parseInt(hours);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const hour12 = hour % 12 || 12;
      return `${hour12}:${minutes} ${ampm}`;
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(amount || 0);
    },
  },
};
</script>

<style scoped>
.bs-confirmation {
  text-align: center;
  padding: 2rem 0;
}

.bs-success-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: #d1fae5;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #059669;
}

.bs-booking-details {
  background: #f9fafb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  text-align: left;
  max-width: 400px;
  margin: 0 auto;
}

.bs-detail-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.bs-detail-row:last-child {
  margin-bottom: 0;
}

.bs-detail-label {
  color: #6b7280;
  font-size: 0.875rem;
}

.bs-detail-value {
  color: #111827;
  text-align: right;
  max-width: 60%;
}
</style>
