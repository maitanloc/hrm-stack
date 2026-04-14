<template>
  <div class="relative inline-block text-left" ref="dropdownRef">
    <button 
      @click="isOpen = !isOpen"
      type="button"
      :class="[
        'w-full flex items-center justify-between gap-2 px-3 h-full bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] hover:border-[var(--sys-brand-solid)] hover:shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[var(--sys-brand-solid)]/10 active:scale-[0.98]',
        buttonClass
      ]"
    >
      <div class="flex items-center gap-2 whitespace-nowrap bg-transparent overflow-hidden">
        <span v-if="selectedIcon" class="material-symbols-outlined text-[18px] text-[var(--sys-brand-solid)] shrink-0">{{ selectedIcon }}</span>
        <span class="whitespace-nowrap truncate">{{ selectedLabel }}</span>
      </div>
      <span class="material-symbols-outlined text-[18px] transition-transform duration-500 text-[var(--sys-icon-default)] shrink-0 opacity-40" :class="isOpen ? 'rotate-180 opacity-100' : ''">expand_more</span>
    </button>

    <transition name="dropdown">
      <div 
        v-if="isOpen"
        class="absolute z-[1001] mt-2 min-w-full bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] rounded-md shadow-2xl overflow-hidden backdrop-blur-xl animate-in fade-in slide-in-from-top-2 duration-300"
      >
        <div class="p-1.5 max-h-[300px] overflow-y-auto custom-scrollbar">
          <div 
            v-for="option in options" 
            :key="option.value"
            @click="selectOption(option)"
            class="px-3 py-2 text-[13px] transition-all cursor-pointer rounded-md mb-0.5 last:mb-0 flex items-center justify-between group/opt whitespace-nowrap min-w-max"
            :class="modelValue === option.value 
              ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] font-semibold' 
              : 'text-[var(--sys-text-primary)] hover:bg-[var(--sys-bg-hover)]'"
          >
            <div class="flex items-center gap-3 bg-transparent pr-4">
               <span v-if="option.icon" class="material-symbols-outlined text-[18px] opacity-40 group-hover/opt:opacity-100 transition-opacity">{{ option.icon }}</span>
               <span class="font-medium">{{ option.label }}</span>
            </div>
            <span v-if="modelValue === option.value" class="material-symbols-outlined text-[18px]">check_circle</span>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  options: {
    type: Array,
    required: true,
  },
  modelValue: {
    required: true
  },
  placeholder: {
    type: String,
    default: 'Vui lòng chọn'
  },
  fullWidth: {
    type: Boolean,
    default: true
  },
  buttonClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const selectedOption = computed(() => {
  return props.options.find(o => o.value === props.modelValue);
});

const selectedLabel = computed(() => {
  return selectedOption.value ? selectedOption.value.label : props.placeholder;
});

const selectedIcon = computed(() => {
  return selectedOption.value?.icon || null;
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
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.95);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid);
}
</style>


