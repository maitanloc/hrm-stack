<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div 
      v-for="stat in summaryStats" 
      :key="stat.label" 
      class="insight-card group relative overflow-hidden bg-[var(--sys-bg-surface)] p-4 rounded-2xl border border-[var(--sys-border-subtle)] shadow-sm hover:shadow-md transition-all duration-300"
    >
      <div class="flex items-center justify-between mb-4">
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110 duration-500', stat.bgClass]">
          <span class="material-symbols-rounded text-[22px]">{{ stat.icon }}</span>
        </div>
        <div v-if="stat.tag" :class="['text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-md border', stat.tagClass]">
          {{ stat.tag }}
        </div>
      </div>
      <div class="space-y-1">
        <p class="text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-[0.15em] opacity-50">{{ stat.label }}</p>
        <p class="text-xl font-bold text-[var(--sys-text-primary)] tracking-tight leading-none">{{ stat.value }}</p>
      </div>
      
      <!-- Subtle background decoration -->
      <div class="absolute -bottom-2 -right-2 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity duration-500">
        <span class="material-symbols-rounded text-6xl">{{ stat.icon }}</span>
      </div>
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
    value: `${store.totalEmployees} nhân viên`, 
    icon: 'groups', 
    bgClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)]' 
  },
  { 
    label: 'Chưa phân ca', 
    value: `${store.unassignedEmployees} người`, 
    icon: 'person_off', 
    bgClass: store.unassignedEmployees > 0 ? 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)]' : 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)]',
    tag: store.unassignedEmployees > 0 ? 'Cần gán' : 'Hoàn tất',
    tagClass: store.unassignedEmployees > 0 ? 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]' : 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]'
  },
  { 
    label: 'Cảnh báo lỗi', 
    value: `${store.warningCount} phát hiện`, 
    icon: 'report_problem', 
    bgClass: store.warningCount > 0 ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]' : 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border border-[var(--sys-border-subtle)]',
    tag: store.warningCount > 0 ? 'Rủi ro' : 'An toàn',
    tagClass: store.warningCount > 0 ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]' : 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border-[var(--sys-border-subtle)]'
  },
  { 
    label: 'Trạng thái tuần', 
    value: store.statusLabel, 
    icon: store.isPublished ? 'verified' : 'history_edu', 
    bgClass: store.isPublished ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)]' : 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)]',
    tag: store.isLockedForEditing ? 'Đã khóa' : 'Dự thảo',
    tagClass: store.isLockedForEditing ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]' : 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]'
  },
]);
</script>

<style scoped>
.insight-card:hover {
  border-color: var(--sys-brand-solid);
}
</style>
