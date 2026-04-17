<template>
  <div class="px-6 py-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 border-l-4 border-indigo-600 pl-3">Xuất Dữ Liệu Bảng Lương (Payroll Export)</h1>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
       <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
           <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
              <input type="date" v-model="filter.fromDate" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" />
           </div>
           <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
              <input type="date" v-model="filter.toDate" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" />
           </div>
           <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Danh sách NV (ID) *</label>
              <input type="text" v-model="filter.employeeIdsStr" placeholder="1,2,3,4,5" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" />
           </div>
           <div class="flex items-end">
              <button @click="fetchPayrollExport" :disabled="loading" class="w-full px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 disabled:bg-indigo-300 transition flex items-center justify-center">
                 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                 <span v-if="loading">Đang xử lý...</span>
                 <span v-else>Load Dữ Liệu Lương</span>
              </button>
           </div>
       </div>
    </div>
    
    <div v-if="exportData && exportData.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Hiển thị dữ liệu lương thô của <b>{{ exportData.length }}</b> nhân sự.
            </div>
            <button @click="downloadCsv" class="px-4 py-2 bg-green-600 text-white text-sm rounded shadow hover:bg-green-700 transition">
                Tải xuống CSV (Excel)
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50">Mã NV</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Họ tên</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phòng ban</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Đủ điều kiện?</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tính công</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vắng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Phép/Lễ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tăng ca (Giờ)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(row, idx) in exportData" :key="idx" class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-indigo-600 sticky left-0 bg-white">{{ row.employee_code || `NV_${row.employee_id}` }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ row.full_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ row.department_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span v-if="row.ready_for_payroll" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full font-semibold">Sẵn sàng</span>
                            <span v-else class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-semibold" title="Còn ngoại lệ chưa xử lý">Cần chốt lỗi</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900">{{ row.present_days }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-medium" :class="row.absent_days > 0 ? 'text-red-600' : 'text-gray-900'">{{ row.absent_days }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-500">{{ row.leave_days }} / {{ row.holiday_days }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-medium text-green-600">{{ row.ot_hours }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div v-else-if="!loading && hasFiltered" class="text-center py-10 bg-white rounded-lg shadow-sm border border-gray-200">
        <p class="text-gray-500">Chưa có dữ liệu xuất.</p>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { apiRequest as requestApi } from '@/services/beApi.js'


const filter = reactive({
    fromDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    toDate: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0],
    employeeIdsStr: '1,2,3,4,5'
})

const exportData = ref(null)
const loading = ref(false)
const hasFiltered = ref(false)

const fetchPayrollExport = async () => {
    loading.value = true
    hasFiltered.value = true
    const empIds = filter.employeeIdsStr.split(',').map(s=>parseInt(s.trim())).filter(n=>n>0);
    if(empIds.length === 0) {
        alert();
        loading.value = false;
        return;
    }
    
    try {
        const res = await requestApi('/timesheet/payroll-export', 'POST', {
            employee_ids: empIds,
            from_date: filter.fromDate,
            to_date: filter.toDate
        })
        if (res.success) {
            exportData.value = res.data;
        }
    } catch (e) {
        alert();
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
        r.late_count + r.early_out_count,
        r.ot_hours,
        r.from_date,
        r.to_date
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
