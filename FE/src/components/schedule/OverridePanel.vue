<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
       <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
         <span class="material-symbols-outlined text-rose-500">history_toggle_off</span>
         Danh sách Ghi đè (Override)
       </h3>
       <button @click="store.setActiveTab(1)" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50 px-3 py-1.5 rounded-lg flex items-center gap-1">
         <span class="material-symbols-outlined text-[16px]">touch_app</span>
         Click ô trên bảng để thêm
       </button>
    </div>

    <!-- Empty State -->
    <div v-if="!store.overrides || store.overrides.length === 0" class="bg-white rounded-2xl border border-slate-200 p-16 text-center shadow-sm">
       <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
         <span class="material-symbols-outlined text-slate-300 text-4xl">edit_calendar</span>
       </div>
       <p class="text-slate-800 font-bold text-lg">Chưa có ghi đè nào</p>
       <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">Chưa có nhân sự nào bị thay đổi lịch khác với lịch nền mặc định trong tuần này.</p>
    </div>

    <!-- Data Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nhân viên</th>
            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Ngày ghi đè</th>
            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Ca mới</th>
            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Lý do</th>
            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="o in store.overrides" :key="o.employee_id + '_' + o.work_date" class="border-b border-slate-100 hover:bg-rose-50/30 transition-colors">
            <td class="p-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-600 border border-slate-200">
                  {{ getInitials(getEmp(o.employee_id)?.full_name) }}
                </div>
                <div>
                  <div class="text-sm font-bold text-slate-800">{{ getEmp(o.employee_id)?.full_name || `NV_${o.employee_id}` }}</div>
                  <div class="text-[10px] text-slate-400 font-black uppercase">{{ getEmp(o.employee_id)?.employee_code }}</div>
                </div>
              </div>
            </td>
            <td class="p-4 text-sm font-bold text-slate-600 text-center">{{ formatDate(o.work_date) }}</td>
            <td class="p-4 text-center">
              <span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded-lg text-[10px] font-black border border-rose-200 uppercase tracking-widest shadow-sm">
                {{ getShiftCode(o.shift_type_id) }}
              </span>
            </td>
            <td class="p-4 text-sm text-slate-600 font-medium">{{ o.reason || 'Sửa lịch thủ công' }}</td>
            <td class="p-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="editOverride(o)" class="p-2 bg-slate-50 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 rounded-lg transition-colors border border-slate-200 hover:border-indigo-200 shadow-sm">
                  <span class="material-symbols-outlined text-[18px]">edit</span>
                </button>
                <button @click="deleteOverride(o)" class="p-2 bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-colors border border-slate-200 hover:border-rose-200 shadow-sm">
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const getEmp = (id) => store.employees?.find(e => e.employee_id === id);
const getShiftCode = (id) => store.shifts?.find(s => s.id === id)?.code || 'N/A';

const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

const getInitials = (name) => {
  if (!name) return 'NV';
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
};

const editOverride = (o) => {
  const emp = getEmp(o.employee_id) || { employee_id: o.employee_id, full_name: 'Nhân viên ' + o.employee_id };
  store.openModal('overrideEdit', { emp, date: o.work_date, override: o });
};

const deleteOverride = (o) => {
  store.openModal('confirmAction', {
    type: 'delete_override',
    employeeId: o.employee_id,
    date: o.work_date,
    title: 'Xóa Ghi đè',
    message: 'Bạn có chắc chắn muốn hủy ghi đè này không? Lịch của nhân sự sẽ trở về lịch nền mặc định.'
  });
};
</script>
