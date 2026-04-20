<template>
  <div class="px-6 py-6 bg-[#F8FAFC] min-h-screen animate-in fade-in duration-500">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span class="material-symbols-outlined text-rose-600">emergency_home</span>
          Theo dõi ngoại lệ (Exceptions)
        </h1>
        <p class="text-slate-500 text-sm mt-1">Phát hiện và xử lý nhanh các trường hợp đi muộn, về sớm, thiếu dữ liệu hoặc sai lệch ca.</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="fetchExceptions" class="p-2.5 bg-white border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all text-slate-600">
           <span class="material-symbols-outlined text-[20px]" :class="{'animate-spin': loading}">refresh</span>
        </button>
      </div>
    </div>

    <!-- Exception KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
             <span class="material-symbols-outlined">running_with_errors</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Tổng lỗi</p>
             <p class="text-xl font-black text-rose-600">{{ exceptionsData.length }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
             <span class="material-symbols-outlined">schedule</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Đi muộn/Sớm</p>
             <p class="text-xl font-black text-amber-600">{{ lateEarlyCount }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
             <span class="material-symbols-outlined">person_off</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Thiếu log</p>
             <p class="text-xl font-black text-indigo-600">{{ missingLogCount }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
             <span class="material-symbols-outlined">task_alt</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Đã xử lý</p>
             <p class="text-xl font-black text-emerald-600">0</p>
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
              <button @click="fetchExceptions" :disabled="loading" class="w-full px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-lg shadow-rose-200 transition font-bold text-sm disabled:opacity-50 flex items-center justify-center gap-2">
                 <span v-if="loading" class="material-symbols-outlined animate-spin text-[20px]">refresh</span>
                 <span v-else class="material-symbols-outlined text-[20px]">search</span>
                 {{ loading ? 'Đang tìm kiếm...' : 'Lọc danh sách lỗi' }}
              </button>
           </div>
       </div>
    </div>
    
    <!-- Data Table -->
    <div v-if="exceptionsData && exceptionsData.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden animate-in fade-in slide-in-from-bottom-2 duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter sticky left-0 bg-slate-50 z-10">Nhân viên</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Ngày làm việc</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Trạng thái</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Phân loại lỗi</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-tighter">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-for="(exc, i) in exceptionsData" :key="i" class="hover:bg-rose-50/20 transition-colors group">
                       <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900 border-l-4 border-rose-500 sticky left-0 bg-white group-hover:bg-rose-50/20 transition-colors shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                          {{ exc.employee_code || `NV_${exc.employee_id}` }}
                       </td>
                       <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">{{ formatDate(exc.work_date) }}</td>
                       <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                           <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-lg text-[9px] font-black uppercase tracking-tight">{{ exc.status }}</span>
                       </td>
                       <td class="px-4 py-4 text-sm">
                           <div class="flex flex-wrap gap-1.5">
                               <span v-for="flag in exc.flags" :key="flag" 
                                     :class="getFlagClass(flag)"
                                     class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-tighter border shadow-sm">
                                 {{ flag }}
                               </span>
                           </div>
                       </td>
                       <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                           <button class="px-4 py-1.5 bg-white border border-slate-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 rounded-lg font-black text-[10px] uppercase tracking-widest transition-all">Xử lý ngay</button>
                       </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fallbacks -->
    <div v-else-if="!loading && hasFiltered" class="text-center py-24 bg-white rounded-3xl border border-slate-200 animate-in fade-in duration-500 shadow-sm relative overflow-hidden">
        <div class="absolute -top-10 -right-10 p-20 opacity-5">
           <span class="material-symbols-outlined text-[200px] text-emerald-500">verified</span>
        </div>
        <div class="relative z-10">
          <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner animate-bounce duration-[2000ms]">
            <span class="material-symbols-outlined text-emerald-500 text-4xl">verified</span>
          </div>
          <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Tuyệt vời, không có lỗi!</h2>
          <p class="text-slate-500 text-sm max-w-xs mx-auto mt-2 leading-relaxed">Dữ liệu chấm công trong khoảng thời gian này hoàn toàn chính xác và sạch ngoại lệ.</p>
        </div>
    </div>

    <div v-else-if="!loading" class="text-center py-20 bg-white rounded-3xl border border-slate-200 border-dashed relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-rose-50/10 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
          <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
             <span class="material-symbols-outlined text-4xl">manage_search</span>
          </div>
          <h2 class="text-xl font-bold text-slate-800">Tìm kiếm ngoại lệ chấm công</h2>
          <p class="text-slate-500 text-sm max-w-sm mx-auto mt-2 leading-relaxed">Chọn phòng ban và khoảng thời gian để rà soát các trường hợp sai lệch dữ liệu công.</p>
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
const exceptionsData = ref([])
const departments = ref([])
const loading = ref(false)
const hasFiltered = ref(false)

const lateEarlyCount = computed(() => {
   return exceptionsData.value.filter(e => e.flags?.some(f => f.includes('LATE') || f.includes('EARLY'))).length;
})

const missingLogCount = computed(() => {
   return exceptionsData.value.filter(e => e.flags?.some(f => f.includes('MISSING') || f.includes('NO_LOG'))).length;
})

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

const formatDate = (date) => new Date(date).toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit' });

const getFlagClass = (flag) => {
   const f = flag.toUpperCase();
   if(f.includes('LATE') || f.includes('EARLY')) return 'bg-amber-50 text-amber-700 border-amber-200';
   if(f.includes('MISSING') || f.includes('NO_LOG')) return 'bg-indigo-50 text-indigo-700 border-indigo-200';
   if(f.includes('OVERLAP') || f.includes('CONFLICT')) return 'bg-rose-50 text-rose-700 border-rose-200';
   return 'bg-slate-50 text-slate-600 border-slate-200';
}

const fetchExceptions = async () => {
    loading.value = true
    hasFiltered.value = true
    
    try {
        const res = await apiRequest('/timesheet/exceptions', {
            method: 'POST', 
            body: {
                department_id: filter.departmentId,
                from_date: filter.fromDate,
                to_date: filter.toDate
            }
        })
        if (res.success) {
            exceptionsData.value = res.data || [];
        } else {
            exceptionsData.value = [];
        }
    } catch (e) {
        console.error("Fetch exceptions error:", e);
        exceptionsData.value = [];
    } finally {
        loading.value = false
    }
}
</script>
