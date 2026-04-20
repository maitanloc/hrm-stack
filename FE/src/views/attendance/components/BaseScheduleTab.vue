<template>
  <div class="flex flex-col gap-6">
    <!-- Quick Actions Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-5 rounded-[28px] bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] shadow-sm">
      <div class="flex items-center gap-2.5">
        <button 
          v-if="['manager', 'director', 'admin'].includes(role)"
          @click="store.openModal('bulkAssign')"
          class="group flex items-center gap-2 px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest text-white bg-[var(--sys-brand-solid)] hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-[var(--sys-brand-solid)]/20 disabled:opacity-50 disabled:shadow-none"
          :disabled="store.isLockedForEditing"
        >
          <span class="material-symbols-rounded text-[18px] group-hover:rotate-12 transition-transform">bolt</span>
          Gán nhanh
        </button>
        <button 
          v-if="['manager', 'director', 'admin'].includes(role)"
          @click="store.openModal('copyWeek')"
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest text-[var(--sys-text-primary)] bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] hover:bg-[var(--sys-bg-hover)] active:scale-95 transition-all disabled:opacity-50"
          :disabled="store.isLockedForEditing"
        >
          <span class="material-symbols-rounded text-[18px]">content_copy</span>
          Copy tuần trước
        </button>
      </div>

      <div v-if="store.isLockedForEditing" class="flex items-center gap-2.5 px-4 py-2 rounded-xl bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
        <span class="material-symbols-rounded text-[16px] text-[var(--sys-brand-solid)]">lock</span>
        <span class="text-[9px] font-black text-[var(--sys-brand-solid)] uppercase tracking-[0.2em]">Lịch nền đã khóa (Ready-only)</span>
      </div>

      <div class="flex items-center gap-3">
        <div class="relative group">
          <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-text-disabled)] group-focus-within:text-[var(--sys-brand-solid)] transition-colors">search</span>
          <input 
            type="text" 
            placeholder="Tìm theo tên hoặc mã nhân sự..." 
            v-model="searchTerm"
            class="pl-11 pr-5 py-2.5 w-72 rounded-xl text-[13px] font-bold bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] focus:ring-4 focus:ring-[var(--sys-brand-solid)]/10 focus:border-[var(--sys-brand-solid)] outline-none transition-all placeholder:text-[var(--sys-text-disabled)] placeholder:font-normal"
          />
        </div>
      </div>
    </div>

    <!-- Weekly Matrix Wrapper -->
    <div class="matrix-wrapper animation-fade-in">
      <WeeklyMatrixGrid :search-term="searchTerm" />
    </div>

    <!-- Modals -->
    <BulkAssignModal v-if="store.modals.bulkAssign?.open" />
    <CopyWeekModal v-if="store.modals.copyWeek?.open" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';
import { useCurrentUser } from '@/composables/useCurrentUser';
import WeeklyMatrixGrid from './WeeklyMatrixGrid.vue';
import BulkAssignModal from './modals/BulkAssignModal.vue';
import CopyWeekModal from './modals/CopyWeekModal.vue';

const store = useScheduleStore();
const { role } = useCurrentUser();
const searchTerm = ref('');
</script>
