<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div 
      v-for="stat in summaryStats" 
      :key="stat.label" 
      class="summary-card group relative overflow-hidden bg-white p-5 rounded-[28px] border border-[var(--sys-border-subtle)] shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-500"
    >
      <div class="flex items-start justify-between relative z-10">
        <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 shadow-sm', stat.bgClass]">
          <span class="material-symbols-rounded text-[28px]">{{ stat.icon }}</span>
        </div>
        <div v-if="stat.tag" :class="['px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-[0.1em] border backdrop-blur-md', stat.tagClass]">
          {{ stat.tag }}
        </div>
      </div>

      <div class="mt-6 space-y-1 relative z-10">
        <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-[0.1em] opacity-60 group-hover:opacity-100 transition-opacity">{{ stat.label }}</p>
        <div class="flex items-baseline gap-2">
          <p class="text-2xl font-black text-[var(--sys-text-primary)] tracking-tight">{{ stat.value }}</p>
          <span v-if="stat.subValue" class="text-[12px] font-bold text-[var(--sys-text-secondary)] opacity-40">{{ stat.subValue }}</span>
        </div>
      </div>
      
      <!-- Subtle background decoration -->
      <div class="absolute -bottom-6 -right-6 opacity-[0.03] group-hover:opacity-[0.08] group-hover:scale-125 transition-all duration-700 pointer-events-none">
        <span class="material-symbols-rounded text-9xl">{{ stat.icon }}</span>
      </div>

      <!-- Bottom progress line (optional decoration) -->
      <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-transparent via-[var(--sys-brand-solid)] to-transparent opacity-0 group-hover:opacity-20 w-full transition-opacity"></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const summaryStats = computed(() => [
  { 
    label: 'Tổng nhân sự', 
    value: store.totalEmployees,
    subValue: 'nhân viên', 
    icon: 'groups', 
    bgClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)]' 
  },
  { 
    label: 'Chưa phân ca', 
    value: store.unassignedEmployees,
    subValue: 'người',
    icon: 'person_off', 
    bgClass: store.unassignedEmployees > 0 ? 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)]' : 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)]',
    tag: store.unassignedEmployees > 0 ? 'Cần gán' : 'Hoàn tất',
    tagClass: store.unassignedEmployees > 0 ? 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]' : 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)]'
  },
  { 
    label: 'Cảnh báo rủi ro', 
    value: store.warningCount,
    subValue: 'phát hiện',
    icon: 'report_problem', 
    bgClass: store.warningCount > 0 ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]' : 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border border-[var(--sys-border-subtle)]',
    tag: store.warningCount > 0 ? 'Rủi ro' : 'An toàn',
    tagClass: store.warningCount > 0 ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]' : 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border border-[var(--sys-border-subtle)]'
  },
  { 
    label: 'Trạng thái tuần', 
    value: store.statusLabel, 
    icon: store.isPublished ? 'verified' : 'history_edu', 
    bgClass: store.isPublished ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)]' : 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)]',
    tag: store.isLockedForEditing ? 'Đã khóa' : 'Dự thảo',
    tagClass: store.isLockedForEditing ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)]' : 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] border border-[var(--sys-border-subtle)]'
  },
]);
</script>

<style scoped>
.summary-card:hover {
  border-color: var(--sys-brand-border);
}
</style>
