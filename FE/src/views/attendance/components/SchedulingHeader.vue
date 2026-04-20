<template>
  <header class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-transparent">
    <div class="space-y-1">
      <div class="flex items-center gap-3">
        <h1 class="text-xl font-bold tracking-tight text-[var(--sys-text-primary)] uppercase">Phân ca làm việc</h1>
        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
          <span class="material-symbols-rounded text-[14px] text-[var(--sys-brand-solid)]">calendar_today</span>
          <span class="text-[10px] font-black uppercase tracking-widest text-[var(--sys-brand-solid)]">
            {{ periodLabel }}
          </span>
        </div>
      </div>
      <p class="text-[13px] text-[var(--sys-text-secondary)]">Thiết lập, điều chỉnh và công bố lịch làm việc cho đơn vị.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2 bg-[var(--sys-bg-surface)] p-1.5 rounded-2xl border border-[var(--sys-border-subtle)] shadow-sm">
      <div class="flex items-center px-3 gap-2 h-9">
        <span class="material-symbols-rounded text-[18px] text-[var(--sys-brand-solid)]">apartment</span>
        <select 
          v-model="store.selectedDepartmentId" 
          class="bg-transparent border-none text-[13px] font-bold text-[var(--sys-text-primary)] focus:ring-0 outline-none cursor-pointer p-0"
          @change="onFilterChange"
        >
          <option :value="null">Tất cả bộ phận</option>
          <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
            {{ dept.department_name }}
          </option>
        </select>
      </div>
      
      <div class="h-4 w-px bg-[var(--sys-border-subtle)]"></div>
      
      <div class="flex items-center px-3 gap-3 h-9">
        <div class="flex items-center gap-2">
          <span class="text-[11px] font-black text-[var(--sys-text-secondary)] uppercase tracking-tighter opacity-50">Từ</span>
          <input 
            type="date" 
            v-model="store.selectedDateRange.from" 
            class="bg-transparent border-none text-[13px] font-bold text-[var(--sys-text-primary)] focus:ring-0 outline-none w-[110px] p-0"
            @change="onFilterChange"
          />
        </div>
        <span class="text-[var(--sys-text-disabled)] font-black">/</span>
        <div class="flex items-center gap-2">
          <span class="text-[11px] font-black text-[var(--sys-text-secondary)] uppercase tracking-tighter opacity-50">Đến</span>
          <input 
            type="date" 
            v-model="store.selectedDateRange.to" 
            class="bg-transparent border-none text-[13px] font-bold text-[var(--sys-text-primary)] focus:ring-0 outline-none w-[110px] p-0"
            @change="onFilterChange"
          />
        </div>
      </div>

      <button 
        @click="onFilterChange"
        class="flex items-center gap-2 px-5 h-9 rounded-xl bg-[var(--sys-brand-solid)] text-white text-[11px] font-black uppercase tracking-widest hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-[var(--sys-brand-solid)]/20 disabled:opacity-50"
        :disabled="store.loading"
      >
        <span class="material-symbols-rounded text-[18px]" :class="{ 'animate-spin': store.loading }">sync</span>
        {{ store.loading ? '...' : 'Tải lịch' }}
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
  if (!store.selectedDateRange.from || !store.selectedDateRange.to) return 'Chưa chọn kỳ';
  const start = new Date(store.selectedDateRange.from);
  const end = new Date(store.selectedDateRange.to);
  return `${start.getDate()}/${start.getMonth() + 1} - ${end.getDate()}/${end.getMonth() + 1}`;
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
