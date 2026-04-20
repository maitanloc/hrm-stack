<template>
  <div class="px-6 py-6 bg-[#F8FAFC] min-h-screen animate-in fade-in duration-500">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span class="material-symbols-outlined text-indigo-600">table_chart</span>
          Bảng công tổng hợp (Timesheet)
        </h1>
        <p class="text-slate-500 text-sm mt-1">Theo dõi chi tiết công làm việc, nghỉ phép và các ngoại lệ chấm công.</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="fetchTimesheet" class="p-2.5 bg-white border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all text-slate-600">
           <span class="material-symbols-outlined text-[20px]" :class="{'animate-spin': loading}">refresh</span>
        </button>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
             <span class="material-symbols-outlined">person</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Nhân sự</p>
             <p class="text-xl font-black text-slate-900">{{ timesheetData.length }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
             <span class="material-symbols-outlined">work_history</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Ngày công</p>
             <p class="text-xl font-black text-emerald-600">{{ totals.present }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
             <span class="material-symbols-outlined">event_busy</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Vắng/Nghỉ</p>
             <p class="text-xl font-black text-rose-600">{{ totals.absent + totals.leave }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
             <span class="material-symbols-outlined">warning</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Ngoại lệ</p>
             <p class="text-xl font-black text-amber-600">{{ totals.exceptions }}</p>
          </div>
       </div>
    </div>
    
    <!-- Filter Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
       <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
           <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Phòng ban</label>
              <select v-model="filter.departmentId" class="w-full bg-slate-50 border-slate-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none border transition-all">
                  <option :value="null">Tất cả phòng ban</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
           </div>
           <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Từ ngày</label>
              <input type="date" v-model="filter.fromDate" class="w-full bg-slate-50 border-slate-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none border transition-all" />
           </div>
           <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Đến ngày</label>
              <input type="date" v-model="filter.toDate" class="w-full bg-slate-50 border-slate-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none border transition-all" />
           </div>
           <div>
              <button @click="fetchTimesheet" :disabled="loading" class="w-full px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-200 transition font-bold text-sm disabled:opacity-50 flex items-center justify-center gap-2">
                 <span v-if="loading" class="material-symbols-outlined animate-spin text-[20px]">refresh</span>
                 <span v-else class="material-symbols-outlined text-[20px]">visibility</span>
                 {{ loading ? 'Đang tải...' : 'Xem bảng công' }}
              </button>
           </div>
       </div>
    </div>
    
    <!-- Data Table -->
    <div v-if="timesheetData && timesheetData.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden animate-in fade-in slide-in-from-bottom-2 duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter sticky left-0 bg-slate-50 z-10">Mã NV</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Tổng công</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter text-emerald-600">Làm việc</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter text-blue-600">Nghỉ phép</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter text-rose-600">Vắng</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter text-amber-600">Đi muộn</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter text-orange-600">Tăng ca</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Ngoại lệ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <template v-for="(emp, i) in timesheetData" :key="i">
                        <tr class="hover:bg-slate-50 cursor-pointer transition-colors group" :class="{'bg-indigo-50/30': expanded[i]}" @click="toggleDetails(i)">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900 sticky left-0 bg-white border-r border-slate-100 shadow-[2px_0_5px_rgba(0,0,0,0.02)] flex items-center shrink-0">
                                <span class="material-symbols-outlined text-[18px] mr-2 transition-transform text-slate-400" :class="{'rotate-90 text-indigo-600': expanded[i]}">chevron_right</span>
                                {{ emp.employee_code || `NV_${emp.employee_id}` }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-black text-slate-700">{{ emp.totals.total_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-bold text-emerald-600">{{ emp.totals.present_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-bold text-blue-600">{{ emp.totals.leave_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-bold text-rose-600">{{ emp.totals.absent_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-amber-600 font-medium">{{ emp.totals.late_count }} ({{ emp.totals.late_minutes }}p)</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-orange-600 font-medium">{{ emp.totals.ot_minutes }}p</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                <span v-if="emp.totals.exception_count > 0" class="px-2 py-0.5 text-[10px] font-black rounded-lg bg-rose-100 text-rose-700 uppercase">
                                    {{ emp.totals.exception_count }} LỖI
                                </span>
                                <span v-else class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span>
                            </td>
                        </tr>
                        <!-- Details Row -->
                        <tr v-if="expanded[i]" class="bg-slate-50">
                            <td colspan="8" class="p-4">
                                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden animate-in zoom-in-95 duration-200">
                                   <table class="min-w-full divide-y divide-slate-100">
                                       <thead class="bg-slate-50/50">
                                           <tr>
                                               <th class="px-4 py-3 text-left text-[9px] font-black text-slate-400 uppercase tracking-widest">Ngày</th>
                                               <th class="px-4 py-3 text-left text-[9px] font-black text-slate-400 uppercase tracking-widest">Trạng thái</th>
                                               <th class="px-4 py-3 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest">Hệ số</th>
                                               <th class="px-4 py-3 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest">Phút làm</th>
                                               <th class="px-4 py-3 text-left text-[9px] font-black text-slate-400 uppercase tracking-widest">Đi muộn/Sớm</th>
                                               <th class="px-4 py-3 text-left text-[9px] font-black text-slate-400 uppercase tracking-widest text-rose-500">Cờ cảnh báo</th>
                                           </tr>
                                       </thead>
                                       <tbody class="divide-y divide-slate-50">
                                            <tr v-for="(day, j) in emp.daily" :key="j" :class="{'bg-rose-50/20': day.exception}">
                                                <td class="px-4 py-2.5 text-xs font-bold text-slate-600">{{ formatDate(day.work_date) }}</td>
                                                <td class="px-4 py-2.5">
                                                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter shadow-sm" 
                                                          :class="getStatusClass(day.status)">
                                                        {{ day.status }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2.5 text-xs text-center font-bold text-slate-400">{{ day.working_coeff }}</td>
                                                <td class="px-4 py-2.5 text-xs text-center font-black text-slate-700">{{ day.worked_minutes }}'</td>
                                                <td class="px-4 py-2.5 text-xs">
                                                    <div class="flex gap-2 font-mono">
                                                      <span v-if="day.late_minutes>0" class="text-amber-600 bg-amber-50 px-1 rounded font-bold">M:{{day.late_minutes}}'</span>
                                                      <span v-if="day.early_out_minutes>0" class="text-rose-400 bg-rose-50 px-1 rounded font-bold">S:{{day.early_out_minutes}}'</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-2.5">
                                                   <div class="flex flex-wrap gap-1">
                                                      <span v-for="flag in day.flags" :key="flag" class="px-1.5 py-0.5 bg-rose-100 text-rose-700 text-[8px] font-black rounded uppercase">{{ flag }}</span>
                                                   </div>
                                                </td>
                                            </tr>
                                       </tbody>
                                   </table>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fallbacks -->
    <div v-else-if="!loading && hasFiltered" class="text-center py-20 bg-white rounded-3xl border border-slate-200 border-dashed animate-in fade-in duration-500">
        <span class="material-symbols-outlined text-slate-200 text-6xl mb-4">search_off</span>
        <p class="text-slate-500 font-bold">Không tìm thấy dữ liệu chấm công</p>
        <p class="text-slate-400 text-sm mt-1">Thử thay đổi bộ lọc hoặc phòng ban cần xem.</p>
    </div>

    <div v-else-if="!loading" class="text-center py-20 bg-white rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 p-10 opacity-5">
           <span class="material-symbols-outlined text-9xl">table_chart</span>
        </div>
        <div class="relative z-10">
          <div class="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
             <span class="material-symbols-outlined text-4xl">calendar_today</span>
          </div>
          <h2 class="text-xl font-bold text-slate-800">Dữ liệu bảng công tổng hợp</h2>
          <p class="text-slate-500 text-sm max-w-sm mx-auto mt-2 leading-relaxed">Chọn phòng ban, khoảng thời gian và nhấn nút để bắt đầu xem chi tiết công của đội ngũ.</p>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { apiRequest } from '@/services/beApi.js'

const filter = reactive({
    fromDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    toDate: new Date().toISOString().split('T')[0],
    departmentId: null
})
const timesheetData = ref([])
const departments = ref([])
const loading = ref(false)
const hasFiltered = ref(false)
const expanded = ref({})

const totals = computed(() => {
   return timesheetData.value.reduce((acc, emp) => {
      acc.present += (emp.totals.present_days || 0);
      acc.leave += (emp.totals.leave_days || 0);
      acc.absent += (emp.totals.absent_days || 0);
      acc.exceptions += (emp.totals.exception_count || 0);
      return acc;
   }, { present: 0, leave: 0, absent: 0, exceptions: 0 });
});

const loadDepartments = async () => {
  try {
    const res = await apiRequest('/departments', { query: { page: 1, per_page: 500 } });
    if (res?.success && res.data) {
      departments.value = res.data.map(d => ({
        id: Number(d.departmentId || d.department_id || d.id),
        name: d.departmentName || d.department_name || d.name
      }));
    }
  } catch (err) {
    console.error('Failed to load departments', err);
  }
};

onMounted(() => loadDepartments());

const formatDate = (date) => new Date(date).toLocaleDateString('vi-VN');

const getStatusClass = (status) => {
    switch(status) {
        case 'P': case 'NS': return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'AB': return 'bg-rose-100 text-rose-800 border border-rose-200';
        case 'L': case 'HD': case 'EO': return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'AL': case 'SL': case 'UNP': return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'CT': case 'REMOTE': case 'H': return 'bg-purple-100 text-purple-800 border border-purple-200';
        case 'OT': return 'bg-orange-100 text-orange-800 border border-orange-200';
        default: return 'bg-slate-100 text-slate-600 border border-slate-200';
    }
}

const fetchTimesheet = async () => {
    loading.value = true
    hasFiltered.value = true
    
    try {
        const res = await apiRequest('/timesheet/period-summary', {
            method: 'POST', 
            body: {
                department_id: filter.departmentId,
                from_date: filter.fromDate,
                to_date: filter.toDate
            }
        })
        if (res.success) {
            timesheetData.value = res.data || [];
            expanded.value = {};
        } else {
            timesheetData.value = [];
        }
    } catch (e) {
        console.error("Fetch timesheet error:", e);
        timesheetData.value = [];
    } finally {
        loading.value = false
    }
}

const toggleDetails = (idx) => {
    expanded.value[idx] = !expanded.value[idx]
}
</script>
