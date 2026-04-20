<template>
  <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm bg-white">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50 border-b border-slate-200 font-black uppercase tracking-tighter text-[10px] text-slate-500">
          <th class="p-3 sticky left-0 bg-slate-50 z-30 w-[50px] text-center shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
            <input 
              type="checkbox" 
              class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
              :checked="isAllSelected"
              @change="store.selectAllEmployees($event.target.checked)"
            />
          </th>
          <th class="p-3 sticky left-[50px] bg-slate-50 z-20 w-[200px] shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
            Nhân sự
          </th>
          <th v-for="day in weekDays" :key="day.date" class="p-3 text-center min-w-[120px] border-l border-slate-100">
            <div>{{ day.label }}</div>
            <div class="text-[9px] font-black text-slate-400 opacity-60">{{ formatDate(day.date) }}</div>
          </th>
        </tr>
      </thead>
      <tbody>
        <!-- Loading -->
        <tr v-if="store.loading" v-for="i in 5" :key="i">
           <td colspan="2" class="p-4 border-b border-slate-100 bg-white sticky left-0 z-10 shadow-[2px_0_5px_rgba(0,0,0,0.02)] animate-pulse">
              <div class="h-8 bg-slate-100 rounded-lg w-full"></div>
           </td>
           <td v-for="j in 7" :key="j" class="p-2 border-b border-l border-slate-100 animate-pulse">
              <div class="h-14 bg-slate-50 rounded-xl w-full"></div>
           </td>
        </tr>
        
        <!-- Data Rows -->
        <tr v-for="emp in store.employees" :key="emp.employee_id" 
            class="hover:bg-slate-50/50 transition-colors border-b border-slate-100 group"
            :class="{ 'bg-indigo-50/30': store.selectedEmployeeIds.has(emp.employee_id) }">
          <td class="p-3 sticky left-0 bg-white group-hover:bg-slate-50 z-30 text-center shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
             <input 
              type="checkbox" 
              class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
              :checked="store.selectedEmployeeIds.has(emp.employee_id)"
              @change="store.toggleEmployeeSelection(emp.employee_id)"
            />
          </td>
          <td class="p-3 sticky left-[50px] bg-white group-hover:bg-slate-50 z-20 shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 flex items-center justify-center text-[10px] font-black shrink-0 border border-indigo-100 shadow-sm transition-transform group-hover:scale-110">
                {{ getInitials(emp.full_name) }}
              </div>
              <div class="min-w-0">
                <div class="text-[13px] font-bold text-slate-800 truncate" :title="emp.full_name">{{ emp.full_name }}</div>
                <div class="text-[9px] text-slate-400 font-black uppercase tracking-widest">{{ emp.employee_code || `NV_${emp.employee_id}` }}</div>
              </div>
            </div>
          </td>

          <td v-for="day in weekDays" :key="day.date" class="p-2 text-center align-middle border-l border-slate-100">
             <div 
               @click="handleCellClick(emp, day.date)"
               class="min-h-[64px] rounded-xl border-2 flex flex-col items-center justify-center cursor-pointer transition-all duration-300 p-1 relative group/cell overflow-hidden shadow-sm"
               :class="getCellClass(emp.employee_id, day.date)"
               :title="store.isLockedForEditing ? 'Lịch đã chốt (Published). Chỉ có thể điều chỉnh qua Ghi đè (Override).' : 'Click để gán ca'"
             >
               <template v-if="getAssignment(emp.employee_id, day.date)">
                 <span class="text-xs font-black uppercase tracking-tighter">{{ getAssignment(emp.employee_id, day.date).shift_code }}</span>
                 <span v-if="hasOverride(emp.employee_id, day.date)" 
                       class="text-[8px] font-black text-rose-600 bg-white px-1.5 py-0.5 rounded mt-1 border border-rose-200 shadow-sm leading-none uppercase animate-in zoom-in">
                   OR
                 </span>
                 <span v-else class="text-[8px] font-black mt-1 opacity-60 uppercase tracking-widest">
                   {{ store.isPublished ? 'Fixed' : 'Base' }}
                 </span>
               </template>
               <template v-else>
                 <span class="material-symbols-outlined text-slate-200 group-hover/cell:text-indigo-400 transition-all text-[20px] group-hover/cell:scale-125">add_circle</span>
                 <span class="text-[8px] text-slate-300 font-black mt-1 group-hover/cell:text-indigo-500 uppercase tracking-widest transition-colors opacity-0 group-hover/cell:opacity-100 italic">Assign</span>
               </template>
               
               <!-- Warning -->
               <div v-if="hasWarning(emp.employee_id, day.date)" 
                    class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-rose-500 rounded-full border-2 border-white flex items-center justify-center shadow-md animate-pulse">
                    <span class="material-symbols-outlined text-white text-[10px] font-black">priority_high</span>
               </div>
             </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const weekDays = computed(() => {
  const days = [];
  if (!store.selectedDateRange.from) return days;
  
  // Use a safe way to iterate days without timezone issues
  const labels = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];
  const startDate = store.selectedDateRange.from; // Assuming YYYY-MM-DD
  
  for (let i = 0; i < 7; i++) {
    const d = new Date(startDate + 'T00:00:00'); // Force local midnight
    d.setDate(d.getDate() + i);
    const dateStr = d.getFullYear() + '-' + 
                    String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(d.getDate()).padStart(2, '0');
    days.push({ date: dateStr, label: labels[i] });
  }
  return days;
});

const isAllSelected = computed(() => {
  return store.employees.length > 0 && store.selectedEmployeeIds.size === store.employees.length;
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return `${d.getDate()}/${d.getMonth() + 1}`;
};

const getInitials = (name) => {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
};

const getAssignment = (empId, date) => {
  const override = store.overrides?.find(o => o.employee_id === empId && o.work_date === date);
  if (override && override.shift_type_id) {
     const shift = store.shifts?.find(s => (s.shift_type_id || s.id) === override.shift_type_id);
     return { ...override, shift_code: shift?.shift_code || shift?.code || '??' };
  }
  const assign = store.assignments?.find(a => a.employee_id === empId && a.work_date === date);
  if (assign) {
    const shift = store.shifts?.find(s => (s.shift_type_id || s.id) === assign.shift_type_id);
    return { ...assign, shift_code: shift?.shift_code || shift?.code || '??' };
  }
  return null;
};

const hasOverride = (empId, date) => store.overrides?.some(o => o.employee_id === empId && o.work_date === date);

const hasWarning = (empId, date) => {
  const w = store.warnings;
  if (!w) return false;
  const check = (list) => list?.some(item => item.employee?.employee_id === empId && item.work_date === date);
  return check(w.unassigned) || check(w.leave_conflicts) || check(w.late_risk) || check(w.overtime_risk);
};

const getCellClass = (empId, date) => {
  const assign = getAssignment(empId, date);
  const override = hasOverride(empId, date);
  const warning = hasWarning(empId, date);
  
  if (!assign) return 'bg-slate-50 border-slate-100 border-dashed hover:border-indigo-300 hover:bg-indigo-50/50';
  
  let base = 'hover:scale-[1.05] hover:z-10 ';
  if (override) {
    base += 'bg-rose-50 border-rose-200 text-rose-800 ';
  } else if (store.isPublished) {
    base += 'bg-emerald-50 border-emerald-200 text-emerald-800 ';
  } else {
    base += 'bg-white border-slate-200 text-slate-800 ';
  }
  
  if (warning) base += 'ring-2 ring-rose-500/30 border-rose-400 !bg-rose-100/50 ';
  
  return base;
};

const handleCellClick = (emp, date) => {
  const assign = getAssignment(emp.employee_id, date);
  // Always open selection modal for single cells to allow shift choice
  store.openModal('overrideEdit', { 
    emp, 
    date, 
    current: assign,
    // Store.isPublished is handled inside ScheduleModals.vue for title/UI
  });
};
</script>
