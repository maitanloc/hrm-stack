<template>
  <div class="px-6 py-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 border-l-4 border-indigo-600 pl-3">Audit Logs Hành Động Hệ Thống</h1>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
       <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
           <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mã NV thực hiện (Rỗng = Tất cả)</label>
              <input type="text" v-model="filter.performer" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" />
           </div>
           <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Loại Entity (VD: SCHEDULE_SCOPE...)</label>
              <input type="text" v-model="filter.type" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" />
           </div>
           <div class="flex items-end">
              <button @click="fetchLogs" :disabled="loading" class="w-full px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 disabled:bg-indigo-300 transition">
                 <span v-if="loading">Đang tải...</span>
                 <span v-else>Tìm Logs</span>
              </button>
           </div>
       </div>
    </div>
    
    <div v-if="logs && logs.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người thực hiện</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đối tượng / ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thay đổi trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metadata (JSON)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="log in logs" :key="log.audit_id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ log.action_timestamp }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-indigo-600">NV_{{ log.performer_id }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                           <span class="font-bold text-gray-700">{{ log.entity_type }}</span><br>
                           <span class="text-xs">{{ log.entity_id }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-700">{{ log.action_type }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                           <div v-if="log.old_state || log.new_state" class="flex items-center space-x-1">
                               <span class="bg-gray-100 px-2 py-0.5 rounded">{{ log.old_state || 'N/A' }}</span>
                               <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                               <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-medium">{{ log.new_state || 'N/A' }}</span>
                           </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 font-mono" style="max-width: 250px; white-space: normal; word-break: break-all;">
                           {{ log.snapshot_metadata || '{}' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div v-else-if="!loading" class="text-center py-10 bg-white rounded-lg shadow-sm border border-gray-200">
        <p class="text-gray-500">Không tìm thấy Audit Logs</p>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { apiRequest as requestApi } from '@/services/beApi.js'

const logs = ref([])
const loading = ref(false)
const filter = ref({
    performer: '',
    type: ''
})

const fetchLogs = async () => {
    loading.value = true;
    try {
        const res = await requestApi('/workflow-governance/audit-logs?per_page=100', 'GET');
        if (res.success) {
            let data = res.data;
            if(filter.value.performer) {
                 data = data.filter(d => String(d.performer_id) === String(filter.value.performer))
            }
            if(filter.value.type) {
                 data = data.filter(d => String(d.entity_type).toLowerCase().includes(String(filter.value.type).toLowerCase()))
            }
            logs.value = data;
        }
    } catch(e) {
        console.error(e)
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetchLogs())
</script>
