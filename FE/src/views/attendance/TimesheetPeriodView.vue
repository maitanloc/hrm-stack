<template>
  <div class="px-6 py-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 border-l-4 border-indigo-600 pl-3">Bảng Công (Timesheet)</h1>
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
              <label class="block text-sm font-medium text-gray-700 mb-1">Danh sách NV (ID cách nhau dấu phẩy)</label>
              <input type="text" v-model="filter.employeeIdsStr" placeholder="1,2,3" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" />
           </div>
           <div class="flex items-end">
              <button @click="fetchTimesheet" :disabled="loading" class="w-full px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 disabled:bg-indigo-300 transition">
                 <span v-if="loading">Đang tải...</span>
                 <span v-else>Xem bảng công</span>
              </button>
           </div>
       </div>
    </div>
    
    <div v-if="timesheetData && timesheetData.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50">Mã NV</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng công</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Công làm</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nghỉ phép</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lễ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vắng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Đi muộn (Lần/Phút)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tăng ca (Phút)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lỗi/Ngoại lệ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="(emp, i) in timesheetData" :key="i">
                        <!-- Employee Summary Row -->
                        <tr class="bg-indigo-50 font-medium cursor-pointer" @click="toggleDetails(i)">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-900 sticky left-0 bg-indigo-50 border-r border-indigo-100 flex items-center">
                                <svg class="w-4 h-4 mr-1 transition-transform" :class="{'rotate-90': expanded[i]}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                NV_{{ emp.employee_id }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-gray-900">{{ emp.totals.total_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-gray-900">{{ emp.totals.present_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-gray-900">{{ emp.totals.leave_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-gray-900">{{ emp.totals.holiday_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-red-600">{{ emp.totals.absent_days }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-orange-600">{{ emp.totals.late_count }} / {{ emp.totals.late_minutes }}p</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-green-600">{{ emp.totals.ot_minutes }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                <span v-if="emp.totals.exception_count > 0" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    {{ emp.totals.exception_count }}
                                </span>
                                <span v-else class="text-green-500">OK</span>
                            </td>
                        </tr>
                        <!-- Employee Daily Details -->
                        <tr v-if="expanded[i]" class="bg-gray-50 border-b-2 border-indigo-200">
                            <td colspan="9" class="p-0">
                                <div class="p-4">
                                   <table class="min-w-full divide-y divide-gray-200 text-sm bg-white rounded shadow-sm border border-gray-200">
                                       <thead class="bg-gray-100">
                                           <tr>
                                               <th class="px-4 py-2 text-left font-medium text-gray-500">Ngày</th>
                                               <th class="px-4 py-2 text-left font-medium text-gray-500">Trạng thái</th>
                                               <th class="px-4 py-2 text-left font-medium text-gray-500">Hệ số</th>
                                               <th class="px-4 py-2 text-left font-medium text-gray-500">Giờ làm</th>
                                               <th class="px-4 py-2 text-left font-medium text-gray-500">Đi muộn/Về sớm</th>
                                               <th class="px-4 py-2 text-left font-medium text-gray-500">Cờ bất thường</th>
                                           </tr>
                                       </thead>
                                       <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(day, j) in emp.daily" :key="j" :class="{'bg-red-50': day.exception}">
                                                <td class="px-4 py-2 text-gray-900 border-r border-gray-100">{{ day.work_date }}</td>
                                                <td class="px-4 py-2">
                                                    <span class="px-2 py-0.5 rounded text-xs font-medium" 
                                                          :class="{
                                                              'bg-green-100 text-green-800': day.status === 'P' || day.status === 'NS',
                                                              'bg-red-100 text-red-800': day.status === 'AB',
                                                              'bg-orange-100 text-orange-800': day.status === 'L' || day.status === 'HD' || day.status === 'EO',
                                                              'bg-blue-100 text-blue-800': day.status === 'AL' || day.status === 'SL' || day.status === 'UNP',
                                                              'bg-purple-100 text-purple-800': day.status === 'CT' || day.status === 'REMOTE' || day.status === 'H',
                                                              'bg-gray-100 text-gray-800': day.status === 'UNASSIGNED',
                                                              'bg-yellow-100 text-yellow-800': day.status === 'OT'
                                                          }">
                                                        {{ day.status }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-gray-700">{{ day.working_coeff }}</td>
                                                <td class="px-4 py-2 text-gray-700">{{ day.worked_minutes }} phút</td>
                                                <td class="px-4 py-2 text-orange-600">
                                                    <span v-if="day.late_minutes>0">M:{{day.late_minutes}}</span>
                                                    <span v-if="day.early_out_minutes>0"> S:{{day.early_out_minutes}}</span>
                                                </td>
                                                <td class="px-4 py-2 text-red-600 font-mono text-xs">
                                                    {{ day.flags.join(', ') }}
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
    <div v-else-if="!loading && hasFiltered" class="text-center py-10 bg-white rounded-lg shadow-sm border border-gray-200">
        <p class="text-gray-500">Không có dữ liệu</p>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { apiRequest as requestApi } from '@/services/beApi.js'


const filter = reactive({
    fromDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    toDate: new Date().toISOString().split('T')[0],
    employeeIdsStr: '1,2,3'
})
const timesheetData = ref(null)
const loading = ref(false)
const hasFiltered = ref(false)
const expanded = ref({})

const fetchTimesheet = async () => {
    loading.value = true
    hasFiltered.value = true
    
    // Parse ids
    const empIds = filter.employeeIdsStr.split(',').map(s=>parseInt(s.trim())).filter(n=>n>0);
    
    if(empIds.length === 0) {
        alert();
        loading.value = false;
        return;
    }
    
    try {
        const res = await requestApi('/timesheet/period-summary', 'POST', {
            employee_ids: empIds,
            from_date: filter.fromDate,
            to_date: filter.toDate
        })
        if (res.success) {
            timesheetData.value = res.data;
            expanded.value = {};
        }
    } catch (e) {
        alert();
    } finally {
        loading.value = false
    }
}

const toggleDetails = (idx) => {
    expanded.value[idx] = !expanded.value[idx]
}
</script>
