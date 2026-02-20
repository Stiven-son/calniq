<template>
  <div class="bs-checkout">
    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: #374151;">
      Your Details
    </h3>

    <div class="bs-form">
      <!-- Name -->
      <div class="bs-field">
        <label class="bs-label">Full Name *</label>
        <input
          type="text"
          :value="customer.name"
          @input="update('name', $event.target.value)"
          placeholder="John Smith"
          class="bs-input"
          required
        >
      </div>

      <!-- Email -->
      <div class="bs-field">
        <label class="bs-label">Email *</label>
        <input
          type="email"
          :value="customer.email"
          @input="update('email', $event.target.value)"
          placeholder="john@example.com"
          class="bs-input"
          required
        >
      </div>

      <!-- Phone -->
      <div class="bs-field">
        <label class="bs-label">Phone *</label>
        <input
          type="tel"
          :value="customer.phone"
          @input="update('phone', $event.target.value)"
          placeholder="(919) 555-0123"
          class="bs-input"
          required
        >
      </div>

      <!-- Address -->
      <div class="bs-field">
        <label class="bs-label">Street Address *</label>
        <input
          type="text"
          :value="customer.address"
          @input="update('address', $event.target.value)"
          placeholder="123 Main St"
          class="bs-input"
          required
        >
      </div>

      <!-- City, State, Zip -->
      <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.75rem;">
        <div class="bs-field">
          <label class="bs-label">City</label>
          <input
            type="text"
            :value="customer.city"
            @input="update('city', $event.target.value)"
            placeholder="Raleigh"
            class="bs-input"
          >
        </div>
        <div class="bs-field">
          <label class="bs-label">State</label>
          <input
            type="text"
            :value="customer.state"
            @input="update('state', $event.target.value)"
            placeholder="NC"
            class="bs-input"
            maxlength="2"
          >
        </div>
        <div class="bs-field">
          <label class="bs-label">ZIP</label>
          <input
            type="text"
            :value="customer.zip"
            @input="update('zip', $event.target.value)"
            placeholder="27601"
            class="bs-input"
          >
        </div>
      </div>

      <!-- Message -->
      <div class="bs-field">
        <label class="bs-label">Special Instructions (optional)</label>
        <textarea
          :value="customer.message"
          @input="update('message', $event.target.value)"
          placeholder="Gate code, parking instructions, etc."
          class="bs-input"
          rows="3"
        ></textarea>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CheckoutForm',

  props: {
    customer: {
      type: Object,
      default: () => ({
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip: '',
        message: '',
      }),
    },
  },

  emits: ['update-customer'],

  methods: {
    update(field, value) {
      this.$emit('update-customer', {
        ...this.customer,
        [field]: value,
      });
    },
  },
};
</script>

<style scoped>
.bs-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.bs-field {
  display: flex;
  flex-direction: column;
}

.bs-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.375rem;
}

.bs-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  width: 100%;
  transition: border-color 0.2s;
}

.bs-input:focus {
  outline: none;
  border-color: var(--bs-primary, #10b981);
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.bs-input::placeholder {
  color: #9ca3af;
}
</style>