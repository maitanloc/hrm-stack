<template>
  <div class="px-6 py-6 bg-[#F8FAFC] min-h-screen animate-in fade-in duration-500">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span class="material-symbols-outlined text-indigo-600">history_edu</span>
          Nhật ký hệ thống (Audit Logs)
        </h1>
        <p class="text-slate-500 text-sm mt-1">Truy vết mọi hành động thay đổi dữ liệu, cấu hình và trạng thái của hệ thống HRM.</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="fetchLogs" class="p-2.5 bg-white border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all text-slate-600">
           <span class="material-symbols-outlined text-[20px]" :class="{'animate-spin': loading}">refresh</span>
        </button>
      </div>
    </div>

    <!-- Audit Summary Strip -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
             <span class="material-symbols-outlined">analytics</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Tổng sự kiện</p>
             <p class="text-xl font-black text-slate-900">{{ logs.length }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
             <span class="material-symbols-outlined">edit_square</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Cập nhật</p>
             <p class="text-xl font-black text-amber-600">{{ updateCount }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
             <span class="material-symbols-outlined">add_circle</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Tạo mới</p>
             <p class="text-xl font-black text-emerald-600">{{ createCount }}</p>
          </div>
       </div>
       <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
             <span class="material-symbols-outlined">delete_forever</span>
          </div>
          <div>
             <p class="text-xs font-bold text-slate-400 uppercase">Đã xóa</p>
             <p class="text-xl font-black text-rose-600">{{ deleteCount }}</p>
          </div>
       </div>
    </div>
    
    <!-- Filter Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
       <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
           <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Mã NV thực hiện</label>
              <input type="text" v-model="filter.performer" placeholder="VD: 1, 2" class="w-full bg-slate-50 border-slate-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none border transition-all" />
           </div>
           <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Loại đối tượng (Entity)</label>
              <select v-model="filter.type" class="w-full bg-slate-50 border-slate-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none border transition-all">
                  <option value="">Tất cả các loại</option>
                  <option value="EMPLOYEE">Nhân viên</option>
                  <option value="SCHEDULE">Phân ca</option>
                  <option value="LEAVE">Nghỉ phép</option>
                  <option value="CONTRACT">Hợp đồng</option>
                  <option value="SYSTEM">Cấu hình hệ thống</option>
              </select>
           </div>
           <div>
              <button @click="fetchLogs" :disabled="loading" class="w-full px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl shadow-lg shadow-slate-200 transition font-bold text-sm disabled:opacity-50 flex items-center justify-center gap-2">
                 <span v-if="loading" class="material-symbols-outlined animate-spin text-[20px]">refresh</span>
                 <span v-else class="material-symbols-outlined text-[20px]">filter_list</span>
                 {{ loading ? 'Đang truy vấn...' : 'Lọc dữ liệu nhật ký' }}
              </button>
           </div>
       </div>
    </div>
    
    <!-- Logs Table -->
    <div v-if="logs && logs.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden animate-in fade-in slide-in-from-bottom-2 duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter sticky left-0 bg-slate-50 z-10">Thời gian</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Người thực hiện</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Đối tượng</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Hành động</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Thay đổi</th>
                        <th class="px-4 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Metadata</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-for="log in logs" :key="log.audit_id" class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap text-[11px] font-bold text-slate-400 font-mono">
                           {{ formatDateTime(log.action_timestamp) }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                               <div class="w-6 h-6 rounded-full bg-slate-100 text-[10px] flex items-center justify-center font-black text-slate-500">NV</div>
                               <span class="text-sm font-bold text-slate-700">NV_{{ log.performer_id }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                           <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[9px] font-black uppercase tracking-tight">{{ log.entity_type }}</span>
                           <p class="text-[10px] text-slate-400 mt-0.5 font-mono">ID: {{ log.entity_id }}</p>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                           <span :class="getActionClass(log.action_type)" class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-tighter border shadow-sm">
                              {{ log.action_type }}
                           </span>
                        </td>
                        <td class="px-4 py-4">
                           <div v-if="log.old_state || log.new_state" class="flex items-center gap-2 text-[10px]">
                               <span class="bg-slate-50 border border-slate-100 px-2 py-0.5 rounded text-slate-400 truncate max-w-[80px]" :title="log.old_state">{{ log.old_state || 'N/A' }}</span>
                               <span class="material-symbols-outlined text-[14px] text-slate-300">trending_flat</span>
                               <span class="bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded text-indigo-700 font-bold truncate max-w-[80px]" :title="log.new_state">{{ log.new_state || 'N/A' }}</span>
                           </div>
                           <span v-else class="text-[10px] text-slate-400 italic">Không có thay đổi trạng thái</span>
                        </td>
                        <td class="px-4 py-4">
                           <div class="max-w-[200px] group-hover:max-w-none transition-all duration-500 overflow-hidden">
                              <pre class="text-[9px] font-mono text-slate-400 bg-slate-50 p-1.5 rounded-lg border border-slate-100">{{ log.snapshot_metadata || '{}' }}</pre>
                           </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fallbacks -->
    <div v-else-if="!loading" class="text-center py-20 bg-white rounded-3xl border border-slate-200 border-dashed">
        <span class="material-symbols-outlined text-slate-200 text-6xl mb-4">history_toggle_off</span>
        <p class="text-slate-500 font-bold">Không tìm thấy bản ghi nhật ký nào</p>
        <p class="text-slate-400 text-sm mt-1">Hệ thống chưa ghi nhận hành động nào trong phạm vi này.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { apiRequest } from '@/services/beApi.js'

const logs = ref([])
const loading = ref(false)
const filter = ref({
    performer: '',
    type: ''
})

const updateCount = computed(() => logs.value.filter(l => l.action_type?.includes('UPDATE')).length)
const createCount = computed(() => logs.value.filter(l => l.action_type?.includes('CREATE')).length)
const deleteCount = computed(() => logs.value.filter(l => l.action_type?.includes('DELETE')).length)

const formatDateTime = (ts) => {
   if(!ts) return '';
   const d = new Date(ts);
   return d.toLocaleString('vi-VN', { hour12: false, month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
}

const getActionClass = (type) => {
   const t = type?.toUpperCase() || '';
   if(t.includes('CREATE')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
   if(t.includes('UPDATE')) return 'bg-amber-50 text-amber-700 border-amber-200';
   if(t.includes('DELETE')) return 'bg-rose-50 text-rose-700 border-rose-200';
   if(t.includes('PUBLISH')) return 'bg-indigo-50 text-indigo-700 border-indigo-200';
   return 'bg-slate-50 text-slate-600 border-slate-200';
}

const fetchLogs = async () => {
    loading.value = true;
    try {
        const res = await apiRequest('/workflow-governance/audit-logs?per_page=100', { method: 'GET' });
        if (res.success) {
            let data = (res.data || []).map((item) => ({
                audit_id: item.audit_log_id,
                performer_id: item.actor_id,
                performer_name: item.actor_name,
                entity_type: item.entity_type,
                entity_id: item.entity_ref,
                action_type: item.action_type,
                old_state: item.from_state,
                new_state: item.to_state,
                snapshot_metadata: item.context_json,
                action_timestamp: item.created_at,
            }));
            if(filter.value.performer) {
                 data = data.filter(d => String(d.performer_id) === String(filter.value.performer))
            }
            if(filter.value.type) {
                 data = data.filter(d => String(d.entity_type).toLowerCase().includes(String(filter.value.type).toLowerCase()))
            }
            logs.value = data;
        }
    } catch(e) {
        console.error("Fetch logs fail", e)
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetchLogs())
</script>
