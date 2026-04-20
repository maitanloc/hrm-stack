<template>
  <div class="px-6 py-6 bg-[#F8FAFC] min-h-screen animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span class="material-symbols-outlined text-indigo-600">payments</span>
          Xuất dữ liệu lương (Payroll Export)
        </h1>
        <p class="text-slate-500 text-sm mt-1">Tổng hợp dữ liệu công, tăng ca và nghỉ phép để xuất file tính lương.</p>
      </div>
      <div class="flex items-center gap-2">
         <button @click="fetchPayrollExport" class="p-2.5 bg-white border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all text-slate-600">
            <span class="material-symbols-outlined text-[20px]" :class="{'animate-spin': loading}">refresh</span>
         </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
             <span class="material-symbols-outlined">groups</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Nhân sự</p>
             <p class="text-xl font-black text-slate-900">{{ exportData?.length || 0 }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
             <span class="material-symbols-outlined">check_circle</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Sẵn sàng</p>
             <p class="text-xl font-black text-emerald-600">{{ readyCount }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
             <span class="material-symbols-outlined">error</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Cần rà soát</p>
             <p class="text-xl font-black text-rose-600">{{ (exportData?.length || 0) - readyCount }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
             <span class="material-symbols-outlined">timer</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Tổng OT (Giờ)</p>
             <p class="text-xl font-black text-amber-600">{{ totalOt }}h</p>
          </div>
       </div>
    </div>
    
    <!-- Modern Filter Bar -->
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
              <button @click="fetchPayrollExport" :disabled="loading" class="w-full px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-200 transition font-bold text-sm disabled:opacity-50 flex items-center justify-center gap-2">
                 <span v-if="loading" class="material-symbols-outlined animate-spin text-[20px]">refresh</span>
                 <span v-else class="material-symbols-outlined text-[20px]">download</span>
                 {{ loading ? 'Đang tổng hợp...' : 'Trích xuất dữ liệu' }}
              </button>
           </div>
       </div>
    </div>
    
    <!-- Data Area -->
    <div v-if="exportData && exportData.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <p class="text-sm font-bold text-slate-600">Dữ liệu trích xuất sẵn sàng ({{ exportData.length }} bản ghi)</p>
            <button @click="downloadCsv" class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-widest rounded-lg shadow-sm transition-all">
                <span class="material-symbols-outlined text-[18px]">download_for_offline</span>
                Tải Excel (CSV)
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter sticky left-0 bg-slate-50 z-10">Mã NV</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Họ tên</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Phòng ban</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Trạng thái</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Công (Ngày)</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Vắng</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Phép/Lễ</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Tăng ca</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-50">
                    <tr v-for="(row, idx) in exportData" :key="idx" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-indigo-600 sticky left-0 bg-white shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                           {{ row.employee_code || `NV_${row.employee_id}` }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-slate-700">{{ row.full_name }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-medium text-slate-500">{{ row.department_name }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                            <span v-if="row.ready_for_payroll" class="px-2 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-lg uppercase tracking-tighter">Sẵn sàng</span>
                            <span v-else class="px-2 py-1 bg-rose-50 text-rose-700 text-[10px] font-black rounded-lg uppercase tracking-tighter">Cần xử lý</span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-black text-slate-700">{{ row.present_days }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-bold" :class="row.absent_days > 0 ? 'text-rose-600' : 'text-slate-400'">{{ row.absent_days }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-slate-500">{{ row.leave_days }} / {{ row.holiday_days }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-black text-amber-600">{{ row.ot_hours }}h</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fallbacks -->
    <div v-else-if="!loading && hasFiltered" class="text-center py-20 bg-white rounded-3xl border border-slate-200 border-dashed">
        <span class="material-symbols-outlined text-slate-200 text-6xl mb-4">search_off</span>
        <p class="text-slate-500 font-bold">Không tìm thấy dữ liệu phù hợp</p>
        <p class="text-slate-400 text-sm mt-1">Vui lòng kiểm tra lại điều kiện lọc hoặc nhân sự đã chọn.</p>
    </div>

    <div v-else-if="!loading" class="text-center py-20 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden relative">
       <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/20 to-transparent pointer-events-none"></div>
       <div class="relative z-10">
          <div class="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
             <span class="material-symbols-outlined text-4xl">payments</span>
          </div>
          <h2 class="text-xl font-bold text-slate-800">Sẵn sàng trích xuất lương</h2>
          <p class="text-slate-500 text-sm max-w-sm mx-auto mt-2 leading-relaxed">Chọn phòng ban, khoảng thời gian để bắt đầu tổng hợp dữ liệu chi tiết.</p>
       </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { apiRequest } from '@/services/beApi.js'

const filter = reactive({
    fromDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    toDate: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0],
    departmentId: null
})

const exportData = ref(null)
const loading = ref(false)
const hasFiltered = ref(false)
const departments = ref([])

const readyCount = computed(() => exportData.value?.filter(r => r.ready_for_payroll).length || 0)
const totalOt = computed(() => exportData.value?.reduce((sum, r) => sum + (parseFloat(r.ot_hours) || 0), 0).toFixed(1) || 0)

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

const fetchPayrollExport = async () => {
    loading.value = true
    hasFiltered.value = true
    
    try {
        const res = await apiRequest('/timesheet/payroll-export', {
            method: 'POST', 
            body: {
                department_id: filter.departmentId,
                from_date: filter.fromDate,
                to_date: filter.toDate
            }
        })
        if (res.success) {
            exportData.value = res.data;
        }
    } catch (e) {
        console.error("Export fail", e);
        alert("Lỗi khi trích xuất dữ liệu: " + e.message);
    } finally {
        loading.value = false
    }
}

const downloadCsv = () => {
    if(!exportData.value || exportData.value.length === 0) return;
    
    const headers = ['Ma_NV', 'Ho_Ten', 'Phong_Ban', 'Du_Dieu_Kien', 'Cong_Thuc_Te', 'Ngay_Vang', 'Ngay_Phep', 'Ngay_Le', 'DSDMuon_VeSom', 'Tang_Ca_Gio', 'Ky_Tu', 'Ky_Den'];
    const rows = exportData.value.map(r => [
        r.employee_code || r.employee_id,
        `"${r.full_name || ''}"`,
        `"${r.department_name || ''}"`,
        r.ready_for_payroll ? 'YES' : 'NO',
        r.present_days,
        r.absent_days,
        r.leave_days,
        r.holiday_days,
        (r.late_count || 0) + (r.early_out_count || 0),
        r.ot_hours,
        filter.fromDate,
        filter.toDate
    ]);
    
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF" 
        + headers.join(',') + "\n"
        + rows.map(e => e.join(',')).join("\n");
        
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `Payroll_Export_${filter.fromDate}_${filter.toDate}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
