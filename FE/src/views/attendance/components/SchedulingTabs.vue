<template>
  <div class="workflow-tabs-wrapper">
    <div class="flex flex-col md:flex-row items-stretch gap-2 bg-white p-2 rounded-[32px] border border-[var(--sys-border-subtle)] shadow-sm">
      <button 
        v-for="(tab, index) in workflowTabs" 
        :key="tab.id"
        @click="store.setActiveTab(tab.id)"
        :class="[
          'relative flex-1 flex items-center gap-4 px-6 py-4 rounded-[24px] transition-all duration-500 group overflow-hidden',
          store.activeTab === tab.id 
            ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] shadow-sm' 
            : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] hover:text-[var(--sys-text-primary)]'
        ]"
      >
        <!-- Step Number / Icon -->
        <div 
          :class="[
            'w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm transition-all duration-500 shrink-0',
            store.activeTab === tab.id 
              ? 'bg-[var(--sys-brand-solid)] text-white scale-110 shadow-lg shadow-[var(--sys-brand-solid)]/30' 
              : 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] group-hover:bg-[var(--sys-brand-soft)] group-hover:text-[var(--sys-brand-solid)]'
          ]"
        >
          <span v-if="store.activeTab === tab.id" class="material-symbols-rounded text-[20px] animate-in fade-in zoom-in duration-500">{{ tab.icon }}</span>
          <span v-else>{{ index + 1 }}</span>
        </div>

        <!-- Label -->
        <div class="flex flex-col items-start min-w-0">
          <span class="text-[10px] font-black uppercase tracking-[0.2em] opacity-40">Bước {{ index + 1 }}</span>
          <span class="text-[13px] font-bold truncate">{{ tab.label }}</span>
        </div>

        <!-- Badge -->
        <div 
          v-if="tab.badge" 
          class="absolute top-3 right-3 min-w-[20px] h-[20px] px-1 flex items-center justify-center text-[10px] font-black rounded-lg bg-[var(--sys-danger-solid)] text-white shadow-lg shadow-[var(--sys-danger-solid)]/20 animate-bounce"
        >
          {{ tab.badge }}
        </div>

        <!-- Active Indicator Line -->
        <transition name="scale-x">
          <div v-if="store.activeTab === tab.id" class="absolute bottom-0 left-6 right-6 h-0.5 bg-[var(--sys-brand-solid)] rounded-full opacity-40"></div>
        </transition>

        <!-- Hover background effect -->
        <div class="absolute inset-0 bg-gradient-to-tr from-[var(--sys-brand-soft)] to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const workflowTabs = computed(() => [
  { id: 1, label: 'Tạo Lịch Nền', icon: 'auto_awesome_motion' },
  { id: 2, label: 'Ghi đè ca', icon: 'edit_calendar', badge: store.overrides.length > 0 ? store.overrides.length : null },
  { id: 3, label: 'Rà soát lỗi', icon: 'verified_user', badge: store.warningCount > 0 ? store.warningCount : null },
  { id: 4, label: 'Chốt & Publish', icon: 'campaign' },
]);
</script>

<style scoped>
.scale-x-enter-active, .scale-x-leave-active {
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  transform-origin: center;
}
.scale-x-enter-from, .scale-x-leave-to {
  transform: scaleX(0);
}

@keyframes animate-in {
  from { opacity: 0; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1); }
}
</style>
