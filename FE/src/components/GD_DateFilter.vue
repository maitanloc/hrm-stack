<template>
  <div class="relative inline-block text-left" ref="dropdownRef">
    <button
      @click="isOpen = !isOpen"
      type="button"
      class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 bg-[var(--bg-card,#fff)] border border-[var(--border,#e5e7eb)] rounded-2xl text-[13px] font-[800] text-[#1d3d70] dark:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-[0_2px_8px_-2px_rgba(0,0,0,0.05)] font-inherit focus:outline-none"
    >
      <span class="material-symbols-rounded text-[18px] text-slate-500" style="font-variation-settings: 'FILL' 1;">calendar_today</span>
      {{ selectedLabel }}
      <span class="material-symbols-rounded text-[16px] text-slate-400 transition-transform duration-300" :class="isOpen ? 'rotate-180' : ''">expand_more</span>
    </button>

    <transition name="dropdown-fade">
      <div
        v-if="isOpen"
        class="absolute right-0 z-40 mt-2 w-[180px] bg-white dark:bg-[#1e2536] border border-slate-200 dark:border-white/10 rounded-xl shadow-xl overflow-hidden py-1"
      >
        <button
          v-for="option in options"
          :key="option.value"
          @click="selectOption(option)"
          class="w-full text-left px-4 py-2.5 text-[13px] font-[700] hover:bg-slate-50 dark:hover:bg-white/5 transition-colors flex items-center justify-between"
          :class="modelValue === option.value ? 'text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/20' : 'text-slate-700 dark:text-slate-300'"
        >
          {{ option.label }}
          <span v-if="modelValue === option.value" class="material-symbols-rounded text-[16px]">check</span>
        </button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const options = [
  { label: 'Hôm nay', value: 'today' },
  { label: '7 ngày qua', value: '7_days' },
  { label: '30 ngày qua', value: '30_days' },
  { label: 'Tháng này', value: 'this_month' },
  { label: 'Năm nay', value: 'this_year' }
];

const selectedLabel = computed(() => {
  const opt = options.find(o => o.value === props.modelValue);
  return opt ? opt.label : '30 ngày qua';
});

const selectOption = (option) => {
  emit('update:modelValue', option.value);
  isOpen.value = false;
};

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false;
  }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<style scoped>
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.95);
}
</style>
