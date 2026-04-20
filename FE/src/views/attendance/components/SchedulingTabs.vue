<template>
  <div class="workflow-tabs-container relative">
    <div class="flex items-center p-1.5 bg-[var(--sys-bg-surface)] rounded-[24px] border border-[var(--sys-border-subtle)] shadow-sm">
      <button 
        v-for="tab in workflowTabs" 
        :key="tab.id"
        @click="store.setActiveTab(tab.id)"
        :class="[
          'relative flex-1 flex items-center justify-center gap-2.5 py-3.5 rounded-2xl text-[12px] font-black uppercase tracking-widest transition-all duration-500 overflow-hidden group',
          store.activeTab === tab.id 
            ? 'bg-[var(--sys-bg-page)] text-[var(--sys-brand-solid)] shadow-md border border-[var(--sys-border-subtle)]' 
            : 'text-[var(--sys-text-secondary)] hover:text-[var(--sys-text-primary)] hover:bg-[var(--sys-bg-hover)]'
        ]"
      >
        <div v-if="store.activeTab === tab.id" class="absolute inset-0 bg-gradient-to-br from-[var(--sys-brand-soft)]/20 to-transparent opacity-50 pointer-events-none"></div>
        <div v-if="store.activeTab === tab.id" class="absolute bottom-1 left-1/2 -translate-x-1/2 w-8 h-1 rounded-full bg-[var(--sys-brand-solid)] shadow-[0_0_8px_var(--sys-brand-solid)]"></div>
        
        <span class="material-symbols-rounded text-[18px] transition-transform group-hover:scale-110 group-active:scale-95">{{ tab.icon }}</span>
        <span class="hidden md:inline">{{ tab.label }}</span>
        
        <div v-if="tab.badge" class="min-w-[18px] h-[18px] px-1 flex items-center justify-center text-[9px] font-black rounded-full bg-[var(--sys-danger-solid)] text-white shadow-sm ring-2 ring-white">
          {{ tab.badge }}
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const workflowTabs = computed(() => [
  { id: 1, label: '1. Tạo Lịch Nền', icon: 'auto_awesome_motion' },
  { id: 2, label: '2. Ghi đè ca', icon: 'edit_calendar', badge: store.overrides.length > 0 ? store.overrides.length : null },
  { id: 3, label: '3. Rà soát lỗi', icon: 'verified_user', badge: store.warningCount > 0 ? store.warningCount : null },
  { id: 4, label: '4. Chốt & Publish', icon: 'campaign' },
]);
</script>
