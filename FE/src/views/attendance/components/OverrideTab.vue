<template>
  <div class="flex flex-col gap-6">
    <div class="p-6 rounded-[32px] bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] shadow-sm">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
          <h3 class="text-xl font-bold tracking-tight text-[var(--sys-text-primary)]">Danh sách Ghi đè & Ngoại lệ</h3>
          <p class="text-sm text-[var(--sys-text-secondary)] mt-1">Quản lý các điều chỉnh lịch làm việc so với lịch nền ban đầu.</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="relative group">
            <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-text-disabled)] group-focus-within:text-[var(--sys-brand-solid)] transition-colors">person_search</span>
            <input 
              type="text" 
              placeholder="Tìm nhân sự..." 
              v-model="filterSearch"
              class="pl-11 pr-5 py-2.5 w-64 rounded-xl text-[13px] font-bold bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] focus:ring-4 focus:ring-[var(--sys-brand-solid)]/10 focus:border-[var(--sys-brand-solid)] outline-none transition-all placeholder:text-[var(--sys-text-disabled)] placeholder:font-normal"
            />
          </div>
          <button 
            @click="store.openModal('overrideEdit')"
            class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-[var(--sys-brand-solid)] text-white text-xs font-bold hover:brightness-110 transition-all shadow-lg shadow-[var(--sys-brand-solid)]/20 shrink-0"
          >
            <span class="material-symbols-rounded text-[18px]">add</span>
            Thêm ghi đè
          </button>
        </div>
      </div>

      <div class="overflow-hidden border border-[var(--sys-border-subtle)] rounded-3xl">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-[var(--sys-bg-page)]/50 border-b border-[var(--sys-border-subtle)]">
              <th class="px-6 py-4 text-[11px] font-black uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Nhân sự</th>
              <th class="px-6 py-4 text-[11px] font-black uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60 text-center">Giai đoạn</th>
              <th class="px-6 py-4 text-[11px] font-black uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Ca ghi đè</th>
              <th class="px-6 py-4 text-[11px] font-black uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Lý do điều chỉnh</th>
              <th class="px-6 py-4 text-[11px] font-black uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="ovr in filteredOverrides" :key="ovr.override_id" class="hover:bg-[var(--sys-bg-hover)] transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div 
                    class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-sm shadow-sm transition-all group-hover:scale-105"
                    :class="getEmployee(ovr.employee_id)?.gender === 'Female' ? 'bg-pink-50 text-pink-500' : 'bg-blue-50 text-blue-500 border-blue-100'"
                  >
                    {{ getEmployee(ovr.employee_id)?.full_name.charAt(0) }}
                  </div>
                  <div class="flex flex-col">
                    <span class="text-sm font-bold text-[var(--sys-text-primary)] transition-colors group-hover:text-[var(--sys-brand-solid)]">{{ getEmployee(ovr.employee_id)?.full_name }}</span>
                    <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] tracking-wider">#{{ getEmployee(ovr.employee_id)?.employee_code }}</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col items-center">
                  <span class="text-sm font-black text-[var(--sys-text-primary)]">{{ formatDate(ovr.work_date) }}</span>
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] opacity-60 uppercase tracking-widest">{{ getDayName(ovr.work_date) }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col gap-1">
                   <span class="px-3 py-1 w-max rounded-full text-[10px] font-black uppercase tracking-widest bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]">
                     {{ getShiftName(ovr.shift_type_id) }}
                   </span>
                   <span class="text-[9px] font-bold text-[var(--sys-text-disabled)] ml-1">{{ getShiftTime(ovr.shift_type_id) }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-start gap-2 p-3 rounded-xl bg-[var(--sys-bg-page)]/50 border border-transparent group-hover:border-[var(--sys-border-subtle)] transition-all">
                   <span class="material-symbols-rounded text-xs mt-0.5 text-[var(--sys-text-disabled)]">chat_bubble</span>
                   <p class="text-xs text-[var(--sys-text-secondary)] italic leading-relaxed">{{ ovr.reason || 'Chưa ghi chú' }}</p>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                  <button 
                    @click="editOverride(ovr)"
                    class="w-9 h-9 rounded-xl flex items-center justify-center text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all"
                  >
                    <span class="material-symbols-rounded text-[20px]">edit_note</span>
                  </button>
                  <button 
                    @click="deleteOverride(ovr)"
                    class="w-9 h-9 rounded-xl flex items-center justify-center text-[var(--sys-text-secondary)] hover:bg-[var(--sys-danger-soft)] hover:text-[var(--sys-danger-text)] transition-all"
                  >
                    <span class="material-symbols-rounded text-[20px]">delete_sweep</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="store.overrides.length === 0">
              <td colspan="5" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center opacity-40">
                  <span class="material-symbols-rounded text-5xl mb-3">clinical_notes</span>
                  <p class="text-sm font-bold uppercase tracking-widest">Không có dữ liệu điều chỉnh</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const filterSearch = ref('');

const filteredOverrides = computed(() => {
  if (!filterSearch.value) return store.overrides;
  const s = filterSearch.value.toLowerCase();
  return store.overrides.filter(ovr => {
    const emp = getEmployee(ovr.employee_id);
    return emp?.full_name.toLowerCase().includes(s) || emp?.employee_code.toLowerCase().includes(s);
  });
});

const getEmployee = (id) => store.employees.find(e => e.employee_id === id);
const getShiftName = (id) => store.shifts.find(s => s.shift_type_id === id)?.shift_name || 'Nghỉ';

const getShiftTime = (id) => {
  const s = store.shifts.find(s => s.shift_type_id === id);
  if (!s) return '';
  return `${s.start_time?.slice(0,5)} - ${s.end_time?.slice(0,5)}`;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

const getDayName = (dateStr) => {
  const dayNames = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  return dayNames[new Date(dateStr).getDay()];
};

const deleteOverride = async (ovr) => {
  if (confirm(`Bạn có chắc chắn muốn xóa điều chỉnh của nhân viên ${getEmployee(ovr.employee_id)?.full_name} ngày ${formatDate(ovr.work_date)}?`)) {
    await store.deleteOverride(ovr.employee_id, ovr.work_date);
  }
};

const editOverride = (ovr) => {
  store.openModal('overrideEdit', { 
    employee: getEmployee(ovr.employee_id),
    date: ovr.work_date,
    currentShiftId: ovr.shift_type_id,
    reason: ovr.reason
  });
};
</script>
