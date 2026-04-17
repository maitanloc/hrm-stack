<template>
  <div class="px-6 py-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 border-l-4 border-red-600 pl-3">Exception Dashboard (Ngoại lệ chấm công)</h1>
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
              <label class="block text-sm font-medium text-gray-700 mb-1">Danh sách NV (ID)</label>
              <input type="text" v-model="filter.employeeIdsStr" placeholder="1,2,3" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" />
           </div>
           <div class="flex items-end">
              <button @click="fetchExceptions" :disabled="loading" class="w-full px-4 py-2 bg-red-600 text-white rounded shadow hover:bg-red-700 disabled:bg-red-300 transition">
                 <span v-if="loading">Đang tải...</span>
                 <span v-else>Tìm ngoại lệ</span>
              </button>
           </div>
       </div>
    </div>
    
    <div v-if="exceptionsData && exceptionsData.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã NV</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái hiện tại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cờ ngoại lệ (Lý do)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác xử lý</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(exc, i) in exceptionsData" :key="i" class="hover:bg-red-50 transition">
                       <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-l-4 border-red-400"> NV_{{ exc.employee_id }} </td>
                       <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ exc.work_date }}</td>
                       <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                           <span class="px-2 py-1 bg-gray-100 rounded text-gray-800 font-medium">{{ exc.status }}</span>
                       </td>
                       <td class="px-4 py-4 text-sm text-red-600 font-mono">
                           <ul class="list-disc pl-4 space-y-1">
                               <li v-for="flag in exc.flags" :key="flag">{{ flag }}</li>
                           </ul>
                       </td>
                       <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                           <button class="text-indigo-600 hover:text-indigo-900 font-medium">Sửa log / Gửi đơn</button>
                       </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div v-else-if="!loading && hasFiltered" class="text-center py-10 bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col items-center justify-center">
        <svg class="w-16 h-16 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <p class="text-lg font-medium text-gray-700">Tuyệt vời! Không có ngoại lệ chấm công nào trong kỳ.</p>
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
const exceptionsData = ref(null)
const loading = ref(false)
const hasFiltered = ref(false)

const fetchExceptions = async () => {
    loading.value = true
    hasFiltered.value = true
    const empIds = filter.employeeIdsStr.split(',').map(s=>parseInt(s.trim())).filter(n=>n>0);
    if(empIds.length === 0) {
        alert();
        loading.value = false;
        return;
    }
    
    try {
        const res = await requestApi('/timesheet/exceptions', 'POST', {
            employee_ids: empIds,
            from_date: filter.fromDate,
            to_date: filter.toDate
        })
        if (res.success) {
            exceptionsData.value = res.data;
        }
    } catch (e) {
        alert();
    } finally {
        loading.value = false
    }
}
</script>
