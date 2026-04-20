<template>
  <header class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6">
    <!-- Title Section -->
    <div class="space-y-2">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-[var(--sys-brand-solid)] flex items-center justify-center shadow-lg shadow-[var(--sys-brand-solid)]/20">
          <span class="material-symbols-rounded text-white text-[28px]">event_note</span>
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-[var(--sys-text-primary)]">Phân ca làm việc</h1>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[13px] text-[var(--sys-text-secondary)]">Quản lý lịch biểu đơn vị</span>
            <div class="w-1 h-1 rounded-full bg-[var(--sys-text-disabled)] opacity-30"></div>
            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
              <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--sys-brand-solid)]">
                Kỳ: {{ periodLabel }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="flex flex-wrap items-center gap-3 bg-[var(--sys-bg-surface)] p-2 rounded-[24px] border border-[var(--sys-border-subtle)] shadow-sm">
      <!-- Department Selector -->
      <div class="flex items-center pl-4 pr-2 gap-3 h-11 bg-[var(--sys-bg-page)] rounded-2xl border border-transparent focus-within:border-[var(--sys-brand-border)] focus-within:bg-white transition-all">
        <span class="material-symbols-rounded text-[20px] text-[var(--sys-brand-solid)]">apartment</span>
        <select 
          v-model="store.selectedDepartmentId" 
          class="bg-transparent border-none text-[13px] font-bold text-[var(--sys-text-primary)] focus:ring-0 outline-none cursor-pointer p-0 pr-8"
          @change="onFilterChange"
        >
          <option :value="null">Tất cả bộ phận</option>
          <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
            {{ dept.department_name }}
          </option>
        </select>
      </div>
      
      <!-- Date Range Picker Mockup -->
      <div class="flex items-center px-4 gap-4 h-11 bg-[var(--sys-bg-page)] rounded-2xl border border-transparent focus-within:border-[var(--sys-brand-border)] focus-within:bg-white transition-all">
        <div class="flex items-center gap-2">
          <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-tighter opacity-40">Từ</span>
          <input 
            type="date" 
            v-model="store.selectedDateRange.from" 
            class="bg-transparent border-none text-[13px] font-bold text-[var(--sys-text-primary)] focus:ring-0 outline-none w-[115px] p-0 cursor-pointer"
            @change="onFilterChange"
          />
        </div>
        <div class="w-px h-4 bg-[var(--sys-border-subtle)]"></div>
        <div class="flex items-center gap-2">
          <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-tighter opacity-40">Đến</span>
          <input 
            type="date" 
            v-model="store.selectedDateRange.to" 
            class="bg-transparent border-none text-[13px] font-bold text-[var(--sys-text-primary)] focus:ring-0 outline-none w-[115px] p-0 cursor-pointer"
            @change="onFilterChange"
          />
        </div>
      </div>

      <!-- Sync Button -->
      <button 
        @click="onFilterChange"
        class="group flex items-center justify-center gap-2 px-6 h-11 rounded-2xl bg-[var(--sys-brand-solid)] text-white text-[12px] font-bold hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-[var(--sys-brand-solid)]/20 disabled:opacity-50"
        :disabled="store.loading"
      >
        <span class="material-symbols-rounded text-[20px]" :class="{ 'animate-spin': store.loading }">
          {{ store.loading ? 'sync' : 'refresh' }}
        </span>
        <span>{{ store.loading ? 'Đang tải...' : 'Tải dữ liệu' }}</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';
import { apiRequest, toIsoLocalDate } from '@/services/beApi.js';

const store = useScheduleStore();
const departments = ref([]);

const periodLabel = computed(() => {
  if (!store.selectedDateRange.from || !store.selectedDateRange.to) return 'Chưa chọn';
  const start = new Date(store.selectedDateRange.from);
  const end = new Date(store.selectedDateRange.to);
  const startStr = `${start.getDate()}/${start.getMonth() + 1}`;
  const endStr = `${end.getDate()}/${end.getMonth() + 1}/${end.getFullYear()}`;
  return `${startStr} - ${endStr}`;
});

const onFilterChange = () => {
  store.fetchScheduleData(
    store.selectedDepartmentId,
    store.selectedDateRange.from,
    store.selectedDateRange.to
  );
};

onMounted(async () => {
  // Set default date range (current week) if not set
  if (!store.selectedDateRange.from) {
    const now = new Date();
    const day = now.getDay() || 7; // 1 (Mon) to 7 (Sun)
    const monday = new Date(now);
    monday.setDate(now.getDate() - day + 1);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    store.selectedDateRange.from = toIsoLocalDate(monday);
    store.selectedDateRange.to = toIsoLocalDate(sunday);
  }

  try {
    const response = await apiRequest('/departments', { query: { per_page: 100 } });
    departments.value = response?.data || [];
    
    // Auto-select first department if none is selected
    if (!store.selectedDepartmentId && departments.value.length > 0) {
      store.selectedDepartmentId = departments.value[0].department_id;
      onFilterChange();
    }
  } catch (error) {
    console.error('Failed to load departments:', error);
  }
});
</script>
