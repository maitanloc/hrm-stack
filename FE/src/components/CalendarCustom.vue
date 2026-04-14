<template>
  <div class="relative inline-block w-full" ref="calendarRef">
    <!-- Trigger Button (Dropdown style) -->
    <button
      type="button"
      @click="toggle"
      class="w-full h-11 px-4 bg-white border border-[var(--sys-border-strong)] rounded-md flex items-center justify-between hover:border-[var(--sys-brand-solid)] transition-all shadow-sm group active:scale-[0.98]"
    >
      <div class="flex items-center gap-3 overflow-hidden">
        <span class="material-symbols-rounded text-[20px] text-[var(--sys-brand-solid)] group-hover:scale-110 transition-transform">calendar_month</span>
        <span :class="['text-[13px] font-bold truncate transition-colors', modelValue ? 'text-[var(--sys-text-primary)]' : 'text-[var(--sys-text-disabled)]']">
          {{ formattedValue || placeholder }}
        </span>
      </div>
      <span :class="['material-symbols-rounded text-[18px] text-[var(--sys-text-secondary)] transition-transform duration-300', isOpen ? 'rotate-180' : '']">expand_more</span>
    </button>

    <!-- Calendar Popover -->
    <Transition name="calendar-pop">
      <div v-if="isOpen" 
        class="absolute z-[2000] mt-2 bg-white border border-[var(--sys-border-subtle)] rounded-xl shadow-2xl p-4 w-72 origin-top transform-gpu backdrop-blur-md bg-white/95"
      >
        <!-- Calendar Header -->
        <div class="flex items-center justify-between mb-4 px-1">
          <button @click="prevMonth" type="button" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all">
            <span class="material-symbols-rounded">chevron_left</span>
          </button>
          <div class="flex flex-col items-center">
            <h4 class="text-[13px] font-black text-[var(--sys-text-primary)] uppercase tracking-widest leading-none">Tháng {{ viewMonth + 1 }}</h4>
            <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] mt-1 tracking-tighter">{{ viewYear }}</p>
          </div>
          <button @click="nextMonth" type="button" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all">
            <span class="material-symbols-rounded">chevron_right</span>
          </button>
        </div>

        <!-- Weekdays -->
        <div class="grid grid-cols-7 gap-1 mb-2">
          <span v-for="day in ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']" :key="day" 
            class="text-[9px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest text-center py-1 opacity-50">
            {{ day }}
          </span>
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 gap-1">
          <div v-for="(day, idx) in daysInMonth" :key="idx" 
            class="aspect-square flex items-center justify-center relative group"
          >
            <button
              v-if="day"
              type="button"
              @click="selectDate(day)"
              :disabled="isPast(day)"
              :class="[
                'w-8 h-8 rounded-md text-[12px] font-bold transition-all relative z-10 flex items-center justify-center',
                isToday(day) ? 'text-[var(--sys-brand-solid)] after:content-[\'\'] after:absolute after:bottom-1 after:w-1 after:h-1 after:bg-[var(--sys-brand-solid)] after:rounded-full' : '',
                isSelected(day) ? 'bg-[var(--sys-brand-solid)] text-white shadow-lg scale-105' : '',
                isPast(day) ? 'text-[var(--sys-text-disabled)] opacity-30 cursor-not-allowed line-through' : (isSelected(day) ? '' : 'text-[var(--sys-text-primary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)]')
              ]"
            >
              {{ day }}
            </button>
          </div>
        </div>

        <!-- Calendar Footer -->
        <div class="mt-4 pt-3 border-t border-[var(--sys-border-subtle)] flex justify-between items-center">
          <button @click="selectToday" type="button" class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest hover:underline px-2 py-1">Hôm nay</button>
          <button @click="isOpen = false" type="button" class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest px-2 py-1 hover:bg-[var(--sys-bg-hover)] rounded border border-transparent hover:border-[var(--sys-border-subtle)]">Đóng</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Chọn ngày...' },
  disablePast: { type: Boolean, default: false },
  minDate: { type: String, default: '' }
});

const emit = defineEmits(['update:modelValue', 'change']);

const calendarRef = ref(null);
const isOpen = ref(false);

const viewMonth = ref(new Date().getMonth());
const viewYear = ref(new Date().getFullYear());

const formattedValue = computed(() => {
  if (!props.modelValue) return '';
  const parts = props.modelValue.split('-');
  if (parts.length < 3) return props.modelValue;
  const [y, m, d] = parts;
  return `${d}/${m}/${y}`;
});

const isPast = (day) => {
  if (!day) return false;
  const checkDate = new Date(viewYear.value, viewMonth.value, day);
  checkDate.setHours(0, 0, 0, 0);

  if (props.disablePast) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (checkDate < today) return true;
  }
  
  if (props.minDate) {
    const min = new Date(props.minDate);
    min.setHours(0, 0, 0, 0);
    if (checkDate < min) return true;
  }
  
  return false;
};

const daysInMonth = computed(() => {
  const firstDay = new Date(viewYear.value, viewMonth.value, 1).getDay();
  const days = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
  
  // Create empty slots for days before the 1st
  const arr = [];
  for (let i = 0; i < (firstDay === 0 ? 0 : firstDay); i++) {
    arr.push(null);
  }
  
  for (let i = 1; i <= days; i++) {
    arr.push(i);
  }
  return arr;
});

const toggle = () => (isOpen.value = !isOpen.value);

const prevMonth = () => {
  if (viewMonth.value === 0) {
    viewMonth.value = 11;
    viewYear.value--;
  } else {
    viewMonth.value--;
  }
};

const nextMonth = () => {
  if (viewMonth.value === 11) {
    viewMonth.value = 0;
    viewYear.value++;
  } else {
    viewMonth.value++;
  }
};

const selectDate = (day) => {
  if (isPast(day)) return;
  const dateStr = `${viewYear.value}-${String(viewMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
  emit('update:modelValue', dateStr);
  emit('change', dateStr);
  isOpen.value = false;
};

const selectToday = () => {
  const today = new Date();
  viewMonth.value = today.getMonth();
  viewYear.value = today.getFullYear();
  selectDate(today.getDate());
};

const isToday = (day) => {
  const today = new Date();
  return (
    day === today.getDate() &&
    viewMonth.value === today.getMonth() &&
    viewYear.value === today.getFullYear()
  );
};

const isSelected = (day) => {
  if (!props.modelValue) return false;
  const target = `${viewYear.value}-${String(viewMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
  return props.modelValue === target;
};

// Sync view when modelValue changes externally
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    const [y, m] = newVal.split('-');
    viewYear.value = parseInt(y);
    viewMonth.value = parseInt(m) - 1;
  }
}, { immediate: true });

// Click outside logic
const handleClickOutside = (e) => {
  if (calendarRef.value && !calendarRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<style scoped>
.calendar-pop-enter-active, .calendar-pop-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.calendar-pop-enter-from, .calendar-pop-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}

/* Custom scrollbar for some modern feel */
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-thumb { background: var(--sys-border-subtle); border-radius: 4px; }
</style>
