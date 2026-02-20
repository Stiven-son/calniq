<template>
  <div class="bs-schedule">
    <h3 class="bs-schedule-title">Schedule</h3>
    <p class="bs-schedule-subtitle">Choose an arrival window.</p>

    <!-- Week Navigation -->
    <div class="bs-week-nav">
      <button 
        class="bs-week-btn" 
        @click="previousWeek" 
        :disabled="!canGoPrevious"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
      </button>

      <div class="bs-week-days">
        <div 
          v-for="day in weekDays" 
          :key="day.date"
          class="bs-day-column"
        >
          <div class="bs-day-header">
            <div class="bs-day-name">{{ day.dayName }}</div>
            <div class="bs-day-date">{{ day.dateStr }}</div>
          </div>
          
          <div class="bs-day-slots">
            <template v-if="day.loading">
  <div class="bs-slot-loading">
    <div class="bs-spinner"></div>
    <span>Checking availability...</span>
  </div>
</template>
            <template v-else-if="day.slots.length === 0">
              <div class="bs-slot-unavailable">Not available</div>
            </template>
            <template v-else>
              <button
                v-for="slot in day.slots"
                :key="slot.start_time"
                class="bs-time-slot"
                :class="{ selected: isSelected(day.date, slot.start_time) }"
                @click="selectSlot(day.date, slot)"
              >
                {{ formatTimeRange(slot.start_time, slot.end_time) }}
              </button>
            </template>
          </div>
        </div>
      </div>

      <button 
        class="bs-week-btn" 
        @click="nextWeek"
        :disabled="!canGoNext"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';

export default {
  name: 'DateTimePicker',

  props: {
    tenant: { type: String, required: true },
    apiUrl: { type: String, required: true },
    selectedDate: { type: String, default: null },
    selectedTime: { type: String, default: null },
  },

  emits: ['select-date', 'select-time'],

  setup(props, { emit }) {
    const weekOffset = ref(0);
    const slotsCache = ref({});
    const loadingDates = ref({});

    const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
    const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

    // Get tomorrow as start date
    const getStartDate = () => {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      tomorrow.setHours(0, 0, 0, 0);
      return tomorrow;
    };

    const weekDays = computed(() => {
      const days = [];
      const start = getStartDate();
      start.setDate(start.getDate() + (weekOffset.value * 7));

      for (let i = 0; i < 7; i++) {
        const date = new Date(start);
        date.setDate(start.getDate() + i);
        const dateStr = formatDateISO(date);
        
        days.push({
          date: dateStr,
          dayName: dayNames[date.getDay()],
          dateStr: `${date.getDate()}-${monthNames[date.getMonth()]}`,
          slots: slotsCache.value[dateStr] || [],
          loading: loadingDates.value[dateStr] || false,
        });
      }
      return days;
    });

    const canGoPrevious = computed(() => weekOffset.value > 0);
    const canGoNext = computed(() => weekOffset.value < 4); // Max 5 weeks ahead

    function formatDateISO(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function formatTimeRange(start, end) {
      return `${formatTime(start)} - ${formatTime(end)}`;
    }

    function formatTime(timeStr) {
      const [hours, minutes] = timeStr.split(':');
      const hour = parseInt(hours);
      const ampm = hour >= 12 ? 'p' : 'a';
      const hour12 = hour % 12 || 12;
      return `${hour12}:${minutes}${ampm}`;
    }

    function isSelected(date, startTime) {
      return props.selectedDate === date && props.selectedTime?.start === startTime;
    }

    function selectSlot(date, slot) {
      emit('select-date', date);
      emit('select-time', { start: slot.start_time, end: slot.end_time });
    }

    function previousWeek() {
      if (canGoPrevious.value) {
        weekOffset.value--;
      }
    }

    function nextWeek() {
      if (canGoNext.value) {
        weekOffset.value++;
        loadWeekSlots();
      }
    }

    async function loadSlots(dateStr) {
      if (slotsCache.value[dateStr] !== undefined) return;
      
      loadingDates.value[dateStr] = true;
      
      try {
        const response = await fetch(`${props.apiUrl}/${props.tenant}/availability?date=${dateStr}`);
        if (!response.ok) throw new Error('Failed to load');
        
        const data = await response.json();
        slotsCache.value[dateStr] = data.slots || [];
      } catch (e) {
        console.error('Error loading slots for', dateStr, e);
        slotsCache.value[dateStr] = [];
      } finally {
        loadingDates.value[dateStr] = false;
      }
    }

    async function loadWeekSlots() {
      const promises = weekDays.value.map(day => loadSlots(day.date));
      await Promise.all(promises);
    }

    onMounted(() => {
      loadWeekSlots();
    });

    watch(weekOffset, () => {
      loadWeekSlots();
    });

    return {
      weekDays,
      canGoPrevious,
      canGoNext,
      formatTimeRange,
      isSelected,
      selectSlot,
      previousWeek,
      nextWeek,
    };
  },
};
</script>

<style scoped>
.bs-schedule {
  padding: 0.5rem 0;
}

.bs-schedule-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.bs-schedule-subtitle {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 1.5rem;
}

.bs-week-nav {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.bs-week-btn {
  flex-shrink: 0;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 50%;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--bs-primary, #10b981);
  margin-top: 2rem;
}

.bs-week-btn:hover:not(:disabled) {
  background: #f3f4f6;
}

.bs-week-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.bs-week-days {
  display: flex;
  flex: 1;
  gap: 0.25rem;
  overflow-x: auto;
}

.bs-day-column {
  flex: 1;
  min-width: 90px;
  display: flex;
  flex-direction: column;
}

.bs-day-header {
  text-align: center;
  padding: 0.5rem;
  background: #f9fafb;
  border-radius: 0.5rem 0.5rem 0 0;
  border: 1px solid #e5e7eb;
  border-bottom: none;
}

.bs-day-name {
  font-weight: 600;
  font-size: 0.75rem;
  color: #374151;
}

.bs-day-date {
  font-size: 0.7rem;
  color: #6b7280;
}

.bs-day-slots {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 0 0 0.5rem 0.5rem;
  min-height: 150px;
}

.bs-slot-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem 0.5rem;
  color: #6b7280;
  font-size: 0.75rem;
}

.bs-spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e5e7eb;
  border-top-color: var(--bs-primary, #10b981);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.bs-slot-unavailable {
  text-align: center;
  color: #9ca3af;
  padding: 0.75rem 0.5rem;
  font-size: 0.75rem;
  background: #f9fafb;
  border-radius: 0.375rem;
}

.bs-time-slot {
  padding: 0.5rem 0.25rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: white;
  cursor: pointer;
  font-size: 0.7rem;
  text-align: center;
  transition: all 0.15s;
  white-space: nowrap;
}

.bs-time-slot:hover {
  border-color: var(--bs-primary, #10b981);
  background: #f0fdf4;
}

.bs-time-slot.selected {
  background: var(--bs-primary, #10b981);
  border-color: var(--bs-primary, #10b981);
  color: white;
}

@media (max-width: 640px) {
  .bs-week-days {
    overflow-x: scroll;
  }
  
  .bs-day-column {
    min-width: 80px;
  }
}
</style>
