<template>
  <div class="matrix-container relative rounded-[24px] border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] overflow-hidden">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left border-separate border-spacing-0">
        <thead>
          <tr>
            <th
              class="sticky left-0 top-0 z-40 bg-[var(--sys-bg-surface)] px-5 py-4 border-b border-r border-[var(--sys-border-subtle)] w-[280px] shadow-[4px_0_12px_-4px_oklch(0_0_0/0.05)]"
            >
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--sys-text-secondary)] opacity-50">Cơ cấu nhân sự</span>
                <span class="text-[9px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest bg-[var(--sys-bg-page)] px-2 py-0.5 rounded-full border border-[var(--sys-border-subtle)]">∑ Hours</span>
              </div>
            </th>
            <th
              v-for="day in calendarDays"
              :key="day.date"
              class="sticky top-0 z-30 bg-[var(--sys-bg-surface)] px-4 py-3 border-b border-[var(--sys-border-subtle)] min-w-[130px] text-center"
            >
              <div class="flex flex-col items-center group/day">
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--sys-text-secondary)] opacity-40 group-hover/day:text-[var(--sys-brand-solid)] transition-colors">{{ day.dayOfWeek }}</span>
                <span :class="['text-sm font-bold', day.isToday ? 'text-[var(--sys-brand-solid)]' : 'text-[var(--sys-text-primary)]']">{{ day.formattedDate }}</span>
                <div v-if="day.isToday" class="mt-1 w-1 h-1 rounded-full bg-[var(--sys-brand-solid)]"></div>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in filteredEmployees" :key="emp.employee_id" class="group/row">
            <td
              class="sticky left-0 z-20 bg-[var(--sys-bg-surface)] px-5 py-3 border-b border-r border-[var(--sys-border-subtle)] shadow-[4px_0_12px_-4px_oklch(0_0_0/0.05)] group-hover/row:bg-[var(--sys-bg-page)] transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                  <div
                    class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs border shadow-sm transition-all group-hover/row:scale-105 group-hover/row:border-[var(--sys-brand-border)] shrink-0"
                    :class="emp.gender === 'Female' ? 'bg-pink-50 text-pink-500 border-pink-100' : 'bg-blue-50 text-blue-500 border-blue-100'"
                  >
                    {{ emp.full_name.charAt(0) }}
                  </div>
                  <div class="flex flex-col min-w-0">
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)] truncate transition-colors group-hover/row:text-[var(--sys-brand-solid)]">{{ emp.full_name }}</span>
                    <span class="text-[9px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-40">#{{ emp.employee_code }}</span>
                  </div>
                </div>
                <div class="flex flex-col items-end shrink-0 ml-2">
                  <span class="text-[12px] font-black text-[var(--sys-text-primary)]">{{ calculateWeeklyHours(emp.employee_id) }}h</span>
                  <div class="w-1.5 h-1.5 rounded-full bg-[var(--sys-success-solid)] opacity-40" title="Valid schedule"></div>
                </div>
              </div>
            </td>
            <td
              v-for="day in calendarDays"
              :key="day.date"
              class="p-1.5 border-b border-[var(--sys-border-subtle)] cursor-pointer transition-all hover:bg-[var(--sys-bg-page)]"
              @click="onCellClick(emp, day.date)"
            >
              <div
                :class="[
                  'shift-cell h-[70px] rounded-2xl p-2 flex flex-col items-center justify-center relative border-2 transition-all duration-300 group/cell',
                  getShiftClass(emp.employee_id, day.date),
                ]"
              >
                <template v-if="getAssignment(emp.employee_id, day.date)">
                  <span class="text-[11px] font-black uppercase tracking-widest">{{ getAssignment(emp.employee_id, day.date).shift_code }}</span>
                  <div class="flex items-center gap-1 mt-1 opacity-50">
                    <span class="material-symbols-rounded text-[10px]">schedule</span>
                    <span class="text-[9px] font-bold">
                      {{ formatTime(getAssignment(emp.employee_id, day.date).start_time) }} - {{ formatTime(getAssignment(emp.employee_id, day.date).end_time) }}
                    </span>
                  </div>
                </template>
                <template v-else>
                  <div class="flex flex-col items-center gap-1 opacity-20 group-hover/cell:opacity-60 transition-opacity">
                    <span class="material-symbols-rounded text-[18px]">add_circle</span>
                    <span class="text-[8px] font-black uppercase tracking-widest">Trống</span>
                  </div>
                </template>

                <!-- Status Indicators -->
                <div class="absolute top-1.5 right-1.5 flex gap-1">
                  <div v-if="hasOverride(emp.employee_id, day.date)" class="w-1.5 h-1.5 rounded-full bg-[var(--sys-warning-solid)] shadow-[0_0_4px_oklch(from_var(--sys-warning-solid)_l_c_h/0.5)]" title="Ghi đè"></div>
                  <div v-if="hasWarning(emp.employee_id, day.date)" class="w-1.5 h-1.5 rounded-full bg-[var(--sys-danger-solid)] shadow-[0_0_4px_oklch(from_var(--sys-danger-solid)_l_c_h/0.5)] animate-pulse" title="Lỗi"></div>
                </div>

                <!-- Lock Indicator -->
                <div v-if="store.isLockedForEditing && !hasOverride(emp.employee_id, day.date)" class="absolute bottom-1 right-1 opacity-20 group-hover/cell:opacity-40">
                  <span class="material-symbols-rounded text-[12px]">lock</span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Editor Modals will be called from store -->
    <OverrideEditModal v-if="store.modals.overrideEdit?.open" />
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useScheduleStore } from "@/stores/useScheduleStore";
import { toIsoLocalDate } from "@/services/beApi.js";
import OverrideEditModal from "./modals/OverrideEditModal.vue";
// Actually, for Phase 1, we can reuse OverrideEditModal if it handles both base and override or have a separate one.
// Let's call it OverrideEditModal for simplicity as requested by Phase 1 workflow (edit cell = override/adjust).

const props = defineProps({
  searchTerm: String,
});

const store = useScheduleStore();

const calendarDays = computed(() => {
  if (!store.selectedDateRange.from) return [];
  const days = [];
  const start = new Date(store.selectedDateRange.from);
  const end = new Date(store.selectedDateRange.to);
  const dayNames = ["CN", "T2", "T3", "T4", "T5", "T6", "T7"];

  let current = new Date(start);
  while (current <= end && days.length < 31) {
    // Limit to 31 days to avoid infinite loop
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
  // Check override first
  const ovr = store.overrides.find(
    (o) => o.employee_id === empId && o.work_date === date
  );
  if (ovr && ovr.shift_type_id) {
    const shift = store.shifts.find((s) => s.shift_type_id === ovr.shift_type_id);
    return {
      shift_code: shift?.shift_code || "N/A",
      start_time: shift?.start_time,
      end_time: shift?.end_time,
      is_override: true,
    };
  }

  // Then base assignment
  const ass = store.assignments.find(
    (a) => a.employee_id === empId && a.work_date === date
  );
  if (ass) {
    const shift = store.shifts.find((s) => s.shift_type_id === ass.shift_type_id);
    return {
      shift_code: shift?.shift_code || "N/A",
      start_time: shift?.start_time,
      end_time: shift?.end_time,
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
  // Mock for now, should calculate based on store data
  return "40.0";
};

const getShiftClass = (empId, date) => {
  const ass = getAssignment(empId, date);
  const isLocked = store.isLockedForEditing;
  
  if (!ass) return "border-dashed border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30";

  if (ass.is_override) {
    return "border-[var(--sys-warning-border)] bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] shadow-sm";
  }

  if (isLocked) {
    return "border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] opacity-60 grayscale-[0.5]";
  }

  return "border-[var(--sys-brand-border)] bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] shadow-sm group-hover/cell:border-[var(--sys-brand-solid)]";
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
.matrix-container {
  box-shadow: 0 10px 30px -10px oklch(0 0 0/0.05);
}

.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: var(--sys-bg-page);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-soft);
}

.shift-cell:hover {
  transform: translateY(-1px) scale(1.02);
  z-index: 10;
}

/* Glass effect for sticky column */
th.sticky, td.sticky {
  backdrop-filter: blur(8px);
  background-color: var(--sys-bg-surface);
}
</style>
