<template>
  <div class="flex flex-col gap-6">
    <!-- Quick Actions Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-6 p-4 rounded-[32px] bg-white border border-[var(--sys-border-subtle)] shadow-sm">
      <div class="flex items-center gap-3">
        <button 
          v-if="['manager', 'director', 'admin'].includes(role)"
          @click="store.openModal('bulkAssign')"
          class="group flex items-center gap-2.5 px-6 py-3 rounded-2xl text-[12px] font-bold text-white bg-[var(--sys-brand-solid)] hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-[var(--sys-brand-solid)]/20 disabled:opacity-50 disabled:shadow-none"
          :disabled="store.isLockedForEditing"
        >
          <span class="material-symbols-rounded text-[20px] group-hover:rotate-12 transition-transform">bolt</span>
          <span>Gán nhanh tuần</span>
        </button>
        <button 
          v-if="['manager', 'director', 'admin'].includes(role)"
          @click="store.openModal('copyWeek')"
          class="flex items-center gap-2.5 px-6 py-3 rounded-2xl text-[12px] font-bold text-[var(--sys-text-primary)] bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] hover:bg-white hover:border-[var(--sys-brand-border)] hover:text-[var(--sys-brand-solid)] active:scale-95 transition-all disabled:opacity-50"
          :disabled="store.isLockedForEditing"
        >
          <span class="material-symbols-rounded text-[20px]">content_copy</span>
          <span>Copy tuần trước</span>
        </button>
      </div>

      <div class="flex items-center gap-6">
        <div v-if="store.isLockedForEditing" class="hidden lg:flex items-center gap-2.5 px-4 py-2 rounded-xl bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
          <span class="material-symbols-rounded text-[18px] text-[var(--sys-brand-solid)] animate-pulse">lock</span>
          <span class="text-[10px] font-black text-[var(--sys-brand-solid)] uppercase tracking-[0.1em]">Lịch nền đã khóa (Read-only)</span>
        </div>

        <div class="relative group">
          <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-[var(--sys-text-disabled)] group-focus-within:text-[var(--sys-brand-solid)] transition-colors">search</span>
          <input 
            type="text" 
            placeholder="Tìm theo tên hoặc mã nhân sự..." 
            v-model="searchTerm"
            class="pl-12 pr-6 py-3 w-80 rounded-2xl text-[13px] font-bold bg-[var(--sys-bg-page)] border border-transparent focus:bg-white focus:border-[var(--sys-brand-border)] focus:ring-4 focus:ring-[var(--sys-brand-solid)]/5 outline-none transition-all placeholder:text-[var(--sys-text-disabled)] placeholder:font-normal"
          />
        </div>
      </div>
    </div>

    <!-- Weekly Matrix Wrapper -->
    <div class="matrix-wrapper animation-fade-in [animation-delay:100ms]">
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
