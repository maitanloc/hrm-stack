
<template>
  <div class="employee-table-container space-y-4">
    <!-- Toolbar: Search & Info -->
    <div class="flex items-center justify-between mb-4">
      <div class="relative w-72">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)] opacity-60">search</span>
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Tìm theo tên hoặc mã NV..." 
          class="w-full h-11 pl-10 pr-4 rounded-md bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-[13px] text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] transition-all shadow-sm"
        >
      </div>
      <div class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest bg-[var(--sys-brand-soft)] px-3 py-1.5 rounded-md border border-[var(--sys-brand-border)]">
        Tổng số: {{ filteredData.length }} bản ghi
      </div>
    </div>

    <!-- Main Table -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead class="bg-[var(--sys-bg-page)]">
          <tr>
            <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)]">Nhân sự</th>
            <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)]">Phòng ban</th>
            <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)]">Vị trí</th>
            <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] text-center">Trạng thái</th>
            <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] text-right">Quản trị</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--sys-border-subtle)]">
          <!-- BINDING DỮ LIỆU ĐÃ PHÂN TRANG -->
          <tr v-for="emp in paginatedData" :key="emp.employee_code" class="hover:bg-[var(--sys-bg-hover)] transition-all">
            <td class="px-4 py-3 whitespace-nowrap">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center text-[13px] font-bold border border-[var(--sys-brand-border)]">
                  {{ emp.full_name[0] }}
                </div>
                <div class="flex flex-col">
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ emp.full_name }}</span>
                  <span class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-tight opacity-70">#{{ emp.employee_code }}</span>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-[12px] font-medium text-[var(--sys-text-primary)] opacity-90">{{ emp.department }}</td>
            <td class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70">{{ emp.position }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="[
                'px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest border',
                emp.status === 'Active' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]'
              ]">
                {{ emp.status === 'Active' ? 'CHÍNH THỨC' : 'THỬ VIỆC' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button class="w-8 h-8 rounded hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] hover:text-[var(--sys-brand-solid)] transition-all">
                <span class="material-symbols-outlined text-[18px]">more_vert</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Controls (Generic Logic) -->
    <div class="flex items-center justify-between mt-6 px-1">
      <div class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest">
        Trang {{ currentPage }} / {{ totalPages }}
      </div>
      <div class="flex items-center gap-2">
        <button 
          @click="currentPage--" 
          :disabled="currentPage === 1"
          class="h-9 px-4 rounded border border-[var(--sys-border-strong)] text-[11px] font-bold text-[var(--sys-text-secondary)] hover:border-[var(--sys-brand-solid)] disabled:opacity-30 disabled:cursor-not-allowed transition-all uppercase"
        >
          Trước đó
        </button>
        <button 
          @click="currentPage++" 
          :disabled="currentPage === totalPages"
          class="h-9 px-4 rounded bg-[var(--sys-brand-solid)] text-white text-[11px] font-bold shadow-lg hover:brightness-110 disabled:opacity-30 disabled:cursor-not-allowed transition-all uppercase"
        >
          Tiếp theo
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useMockData } from '@/composables/useMockData';

// 1. Consumer Pattern: Sử dụng Composables để lấy 200 bản ghi
const { employees } = useMockData();

// 2. Logic Tìm kiếm (Filtering)
const searchQuery = ref('');
const filteredData = computed(() => {
  if (!searchQuery.value) return employees.value;
  const q = searchQuery.value.toLowerCase();
  return employees.value.filter(item => 
    item.full_name.toLowerCase().includes(q) || 
    item.employee_code.toLowerCase().includes(q)
  );
});

// 3. Logic Phân trang (Pagination)
const pageSize = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => Math.ceil(filteredData.value.length / pageSize.value));

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return filteredData.value.slice(start, end);
});

// Reset về trang 1 khi tìm kiếm thay đổi
watch(searchQuery, () => {
  currentPage.value = 1;
});
</script>

<style scoped>
/* Thừa hưởng design system của HRM */
</style>
