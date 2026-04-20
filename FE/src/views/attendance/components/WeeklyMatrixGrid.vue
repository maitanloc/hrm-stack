<template>
  <div class="matrix-container relative rounded-[32px] border border-[var(--sys-border-subtle)] bg-white overflow-hidden shadow-xl">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left border-separate border-spacing-0">
        <thead>
          <tr>
            <th
              class="sticky left-0 top-0 z-40 bg-white/95 backdrop-blur-md px-6 py-5 border-b border-r border-[var(--sys-border-subtle)] w-[300px] shadow-[4px_0_24px_-4px_oklch(0_0_0/0.05)]"
            >
              <div class="flex items-center justify-between">
                <div class="flex flex-col">
                  <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--sys-text-secondary)] opacity-40">Nhân sự</span>
                  <span class="text-[13px] font-black text-[var(--sys-text-primary)]">Danh sách đơn vị</span>
                </div>
                <div class="flex flex-col items-end">
                  <span class="text-[9px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest">∑ Hours</span>
                </div>
              </div>
            </th>
            <th
              v-for="day in calendarDays"
              :key="day.date"
              class="sticky top-0 z-30 bg-white/95 backdrop-blur-md px-4 py-4 border-b border-[var(--sys-border-subtle)] min-w-[140px] text-center"
            >
              <div :class="['flex flex-col items-center group/day p-2 rounded-2xl transition-all', day.isToday ? 'bg-[var(--sys-brand-soft)]/50' : 'hover:bg-[var(--sys-bg-page)]']">
                <span :class="['text-[10px] font-black uppercase tracking-[0.2em] transition-colors', day.isToday ? 'text-[var(--sys-brand-solid)]' : 'text-[var(--sys-text-secondary)] opacity-40 group-hover/day:opacity-100']">
                  {{ day.dayOfWeek }}
                </span>
                <span :class="['text-[15px] font-black mt-0.5', day.isToday ? 'text-[var(--sys-brand-solid)]' : 'text-[var(--sys-text-primary)]']">
                  {{ day.formattedDate }}
                </span>
                <div v-if="day.isToday" class="mt-1 w-5 h-1 rounded-full bg-[var(--sys-brand-solid)] shadow-[0_0_8px_var(--sys-brand-solid)]"></div>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--sys-border-subtle)]">
          <tr v-for="emp in filteredEmployees" :key="emp.employee_id" class="group/row">
            <td
              class="sticky left-0 z-20 bg-white/95 backdrop-blur-md px-6 py-4 border-r border-[var(--sys-border-subtle)] shadow-[4px_0_24px_-4px_oklch(0_0_0/0.05)] group-hover/row:bg-[var(--sys-bg-page)] transition-all duration-300"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 min-w-0">
                  <div
                    class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-sm border shadow-sm transition-all duration-500 group-hover/row:scale-110 group-hover/row:rotate-3 shrink-0"
                    :class="emp.gender === 'Female' ? 'bg-pink-50 text-pink-500 border-pink-100' : 'bg-blue-50 text-blue-500 border-blue-100'"
                  >
                    {{ emp.full_name.charAt(0) }}
                  </div>
                  <div class="flex flex-col min-w-0">
                    <span class="text-[14px] font-bold text-[var(--sys-text-primary)] truncate transition-colors group-hover/row:text-[var(--sys-brand-solid)]">{{ emp.full_name }}</span>
                    <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] opacity-40 uppercase tracking-widest">#{{ emp.employee_code }}</span>
                  </div>
                </div>
                <div class="flex flex-col items-end shrink-0 ml-3">
                  <span class="text-[13px] font-black text-[var(--sys-text-primary)]">{{ calculateWeeklyHours(emp.employee_id) }}h</span>
                  <div class="flex gap-0.5 mt-1">
                    <div v-for="i in 5" :key="i" class="w-1 h-1 rounded-full" :class="i <= 4 ? 'bg-[var(--sys-success-solid)]' : 'bg-[var(--sys-border-subtle)]'"></div>
                  </div>
                </div>
              </div>
            </td>
            <td
              v-for="day in calendarDays"
              :key="day.date"
              class="p-2 transition-all duration-300 group-hover/row:bg-[var(--sys-bg-page)]/30"
            >
              <div
                @click="onCellClick(emp, day.date)"
                :class="[
                  'shift-cell h-[80px] rounded-[22px] p-3 flex flex-col items-center justify-center relative border-2 transition-all duration-500 cursor-pointer group/cell',
                  getShiftClass(emp.employee_id, day.date),
                ]"
              >
                <template v-if="getAssignment(emp.employee_id, day.date)">
                  <span class="text-[12px] font-black uppercase tracking-[0.15em] transition-transform group-hover/cell:scale-110">
                    {{ getAssignment(emp.employee_id, day.date).shift_code }}
                  </span>
                  <div class="flex items-center gap-1.5 mt-1.5 opacity-60">
                    <span class="material-symbols-rounded text-[11px]">schedule</span>
                    <span class="text-[10px] font-bold whitespace-nowrap">
                      {{ formatTime(getAssignment(emp.employee_id, day.date).start_time) }} - {{ formatTime(getAssignment(emp.employee_id, day.date).end_time) }}
                    </span>
                  </div>
                </template>
                <template v-else>
                  <div class="flex flex-col items-center gap-1 opacity-0 group-hover/cell:opacity-40 transition-all duration-500 scale-90 group-hover/cell:scale-100">
                    <span class="material-symbols-rounded text-[20px]">add_circle</span>
                    <span class="text-[9px] font-black uppercase tracking-widest">Gán ca</span>
                  </div>
                  <!-- Subtle Empty State Icon -->
                  <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] group-hover/cell:opacity-0 transition-opacity">
                    <span class="material-symbols-rounded text-3xl">calendar_today</span>
                  </div>
                </template>

                <!-- Status Indicators -->
                <div class="absolute top-2 right-2 flex gap-1.5">
                  <transition name="pop">
                    <div v-if="hasOverride(emp.employee_id, day.date)" class="w-2.5 h-2.5 rounded-full bg-[var(--sys-warning-solid)] border-2 border-white shadow-lg shadow-[var(--sys-warning-solid)]/30" title="Đã ghi đè"></div>
                  </transition>
                  <transition name="pop">
                    <div v-if="hasWarning(emp.employee_id, day.date)" class="w-2.5 h-2.5 rounded-full bg-[var(--sys-danger-solid)] border-2 border-white shadow-lg shadow-[var(--sys-danger-solid)]/30 animate-pulse" title="Phát hiện lỗi"></div>
                  </transition>
                </div>

                <!-- Action Hint -->
                <div class="absolute bottom-2 right-2 opacity-0 group-hover/cell:opacity-40 transition-opacity">
                  <span class="material-symbols-rounded text-[14px]">edit_note</span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="filteredEmployees.length === 0" class="flex flex-col items-center justify-center py-24 bg-[var(--sys-bg-page)]/30">
       <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center shadow-xl border border-[var(--sys-border-subtle)] mb-6">
         <span class="material-symbols-rounded text-4xl text-[var(--sys-text-disabled)] animate-pulse">person_search</span>
       </div>
       <h3 class="text-lg font-bold text-[var(--sys-text-primary)]">Không tìm thấy nhân sự</h3>
       <p class="text-[13px] text-[var(--sys-text-secondary)] mt-1">Vui lòng thử lại với từ khóa khác.</p>
    </div>

    <!-- Editor Modals -->
    <OverrideEditModal v-if="store.modals.overrideEdit?.open" />
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useScheduleStore } from "@/stores/useScheduleStore";
import { toIsoLocalDate } from "@/services/beApi.js";
import OverrideEditModal from "./modals/OverrideEditModal.vue";

const props = defineProps({
  searchTerm: String,
});

const store = useScheduleStore();

const calendarDays = computed(() => {
  if (!store.selectedDateRange.from) return [];
  const days = [];
  const start = new Date(store.selectedDateRange.from);
  const end = new Date(store.selectedDateRange.to);
  const dayNames = ["CN", "Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7"];

  let current = new Date(start);
  while (current <= end && days.length < 31) {
    days.push({
      date: toIsoLocalDate(current),
      dayOfWeek: dayNames[current.getDay()],
      formattedDate: `${current.getDate()}/${current.getMonth() + 1}`,
      isToday: toIsoLocalDate(current) === toIsoLocalDate(new Date()),
    });
    current.setDate(current.getDate() + 1);
  }
  return days;
});

const filteredEmployees = computed(() => {
  if (!props.searchTerm) return store.employees;
  const s = props.searchTerm.toLowerCase();
  return store.employees.filter(
    (e) =>
      e.full_name.toLowerCase().includes(s) || e.employee_code.toLowerCase().includes(s)
  );
});

const getAssignment = (empId, date) => {
  const ovr = store.overrides.find(
    (o) => o.employee_id === empId && o.work_date === date
  );
  if (ovr && ovr.shift_type_id) {
    const shift = store.shifts.find((s) => s.shift_type_id === ovr.shift_type_id);
    return {
      shift_code: shift?.shift_code || "N/A",
      start_time: shift?.start_time,
      end_time: shift?.end_time,
      shift_type_id: ovr.shift_type_id,
      is_override: true,
    };
  }

  const ass = store.assignments.find(
    (a) => a.employee_id === empId && a.work_date === date
  );
  if (ass) {
    const shift = store.shifts.find((s) => s.shift_type_id === ass.shift_type_id);
    return {
      shift_code: shift?.shift_code || "N/A",
      start_time: shift?.start_time,
      end_time: shift?.end_time,
      shift_type_id: ass.shift_type_id,
      is_override: false,
    };
  }

  return null;
};

const hasOverride = (empId, date) => {
  return store.overrides.some(
    (o) => o.employee_id === empId && o.work_date === date && o.shift_type_id !== null
  );
};

const hasWarning = (empId, date) => {
  return store.allWarnings.some((w) => w.employee_id === empId && w.work_date === date);
};

const formatTime = (time) => {
  if (!time) return "";
  return time.slice(0, 5);
};

const calculateWeeklyHours = (empId) => {
  const total = store.assignments.filter(a => a.employee_id === empId).length * 8;
  return total.toFixed(1);
};

const getShiftClass = (empId, date) => {
  const ass = getAssignment(empId, date);
  const isLocked = store.isLockedForEditing;
  
  if (!ass) return "border-dashed border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/20 hover:border-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)]/20";

  if (ass.is_override) {
    return "border-[var(--sys-warning-border)] bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] shadow-sm hover:shadow-md hover:border-[var(--sys-warning-solid)]";
  }

  if (isLocked) {
    return "border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] opacity-60 grayscale-[0.5]";
  }

  return "border-[var(--sys-brand-border)] bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] shadow-sm hover:shadow-md hover:border-[var(--sys-brand-solid)]";
};

const onCellClick = (emp, date) => {
  const currentAss = getAssignment(emp.employee_id, date);
  store.openModal("overrideEdit", {
    employee: emp,
    date: date,
    currentShiftId: currentAss?.shift_type_id,
  });
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 8px;
  width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: var(--sys-bg-page);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-border);
}

.shift-cell:hover {
  transform: translateY(-2px);
  z-index: 10;
}

.pop-enter-active {
  animation: pop-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes pop-in {
  0% { transform: scale(0); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

th.sticky, td.sticky {
  background-color: rgba(255, 255, 255, 0.95);
}
</style>
