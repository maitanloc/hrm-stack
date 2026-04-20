<template>
  <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <!-- Header Governance -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-900">Quản trị & Báo cáo</h2>
        <p class="text-sm text-slate-500">Giám sát lịch sử thay đổi, truy vết publish và phân tích dữ liệu phân ca.</p>
      </div>
      <div class="flex items-center gap-3">
         <button 
           @click="store.openModal('exportSettings')"
           class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-200"
         >
           <span class="material-symbols-outlined text-[18px]">download</span>
           Xuất dữ liệu
         </button>
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
       <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Tỷ lệ phủ lịch</p>
          <div class="flex items-end justify-between">
             <p class="text-2xl font-black text-slate-900">{{ 100 - Math.round((store.unassignedEmployees / (store.totalEmployees || 1)) * 100) }}%</p>
             <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg">+2% vs tuần trước</span>
          </div>
          <div class="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
             <div class="h-full bg-blue-500 transition-all duration-1000" :style="{ width: (100 - (store.unassignedEmployees / (store.totalEmployees || 1)) * 100) + '%' }"></div>
          </div>
       </div>

       <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Tổng thay đổi (Tuần)</p>
          <p class="text-2xl font-black text-slate-900">{{ store.auditLogs.length }}</p>
          <p class="text-[10px] text-slate-500 mt-1">Bao gồm cả gán nền & ghi đè</p>
       </div>

       <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Ghi đè (Override)</p>
          <p class="text-2xl font-black text-amber-600">{{ store.overrides.length }}</p>
          <p class="text-[10px] text-slate-500 mt-1">Thay đổi sau khi có lịch nền</p>
       </div>

       <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Số lần Publish</p>
          <p class="text-2xl font-black text-emerald-600">{{ store.publishLogs.length }}</p>
          <p class="text-[10px] text-slate-500 mt-1">Lịch sử chốt lịch chính thức</p>
       </div>
    </div>

    <!-- Tab Governance (Audit vs History) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
       <div class="flex border-b border-slate-100">
          <button 
            v-for="sub in subTabs" :key="sub.id"
            @click="activeSub = sub.id"
            class="px-6 py-4 text-xs font-bold transition-all border-b-2"
            :class="activeSub === sub.id ? 'border-blue-600 text-blue-600 bg-blue-50/10' : 'border-transparent text-slate-500 hover:text-slate-700'"
          >
            {{ sub.name }}
          </button>
       </div>

       <div class="p-6 min-h-[400px]">
          <!-- Audit Trail List -->
          <div v-if="activeSub === 'audit'" class="space-y-4">
             <div v-if="store.auditLogs.length === 0" class="flex flex-col items-center justify-center py-20 opacity-40">
                <span class="material-symbols-outlined text-5xl mb-2">list_alt</span>
                <p class="text-sm font-bold">Chưa có bản ghi audit nào</p>
             </div>
             <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                   <thead class="text-slate-500 font-bold bg-slate-50/50">
                      <tr>
                         <th class="p-4 rounded-l-2xl">Thời gian</th>
                         <th class="p-4">Người thực hiện</th>
                         <th class="p-4">Hành động</th>
                         <th class="p-4">Chi tiết thay đổi</th>
                         <th class="p-4 rounded-r-2xl">Trạng thái</th>
                      </tr>
                   </thead>
                   <tbody class="divide-y divide-slate-100">
                      <tr v-for="log in store.auditLogs" :key="log.log_id" class="hover:bg-slate-50 transition-colors">
                         <td class="p-4 text-slate-500 text-xs font-mono">{{ formatDate(log.created_at) }}</td>
                         <td class="p-4">
                            <div class="flex items-center gap-2">
                               <div class="w-6 h-6 rounded-full bg-slate-100 text-[10px] flex items-center justify-center font-bold text-slate-600 uppercase">{{ log.actor_name?.[0] || 'S' }}</div>
                               <span class="font-bold text-slate-700">{{ log.actor_name || 'System' }}</span>
                            </div>
                         </td>
                         <td class="p-4">
                            <span class="px-2 py-1 rounded-lg font-bold text-[10px] uppercase tracking-tighter" :class="getActionClass(log.action_type)">
                               {{ log.action_type }}
                            </span>
                         </td>
                         <td class="p-4">
                            <p class="text-[11px] text-slate-600 leading-relaxed">{{ log.description }}</p>
                         </td>
                         <td class="p-4 text-emerald-600">
                            <span class="material-symbols-outlined text-[18px]">verified_user</span>
                         </td>
                      </tr>
                   </tbody>
                </table>
             </div>
          </div>

          <!-- Publish History -->
          <div v-if="activeSub === 'publish'" class="space-y-4">
             <div v-if="store.publishLogs.length === 0" class="flex flex-col items-center justify-center py-20 opacity-40">
                <span class="material-symbols-outlined text-5xl mb-2">history</span>
                <p class="text-sm font-bold">Chưa có lịch sử publish nào</p>
             </div>
             <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="pub in store.publishLogs" :key="pub.log_id" class="p-4 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/10 transition-all group">
                   <div class="flex items-center justify-between mb-3">
                      <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-black uppercase">Published</span>
                      <span class="text-[10px] font-mono text-slate-400">{{ formatDate(pub.published_at) }}</span>
                   </div>
                   <h4 class="font-bold text-slate-800">Tuần {{ formatWeek(pub.from_date) }}</h4>
                   <p class="text-xs text-slate-500 mt-1">Người chốt: <span class="font-bold text-slate-700">{{ pub.published_by_name }}</span></p>
                   <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 opacity-60 group-hover:opacity-100">
                      <div class="flex gap-4">
                         <div class="text-[10px]">
                            <p class="text-slate-400 font-bold uppercase">Nhân sự</p>
                            <p class="text-sm font-black text-slate-700">{{ pub.meta?.employee_count || 'N/A' }}</p>
                         </div>
                         <div class="text-[10px]">
                            <p class="text-slate-400 font-bold uppercase">Phòng ban</p>
                            <p class="text-sm font-black text-slate-700">{{ pub.scope_id }}</p>
                         </div>
                      </div>
                      <button class="text-xs font-bold text-emerald-600 hover:underline">Xem snapshot</button>
                   </div>
                </div>
             </div>
          </div>
       </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const activeSub = ref('audit');

const subTabs = [
  { id: 'audit', name: 'Lịch sử thay đổi (Audit Trail)' },
  { id: 'publish', name: 'Lịch sử Publish' },
];

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('vi-VN', { 
    hour: '2-digit', minute: '2-digit', 
    day: '2-digit', month: '2-digit', year: 'numeric' 
  });
};

const formatWeek = (dateStr) => {
   const d = new Date(dateStr);
   const oneJan = new Date(d.getFullYear(),0,1);
   const numberOfDays = Math.floor((d - oneJan) / (24 * 60 * 60 * 1000));
   const week = Math.ceil(( d.getDay() + 1 + numberOfDays) / 7);
   return `${week}/${d.getFullYear()}`;
};

const getActionClass = (type) => {
  const t = type?.toUpperCase();
  if (t?.includes('OVERRIDE')) return 'bg-amber-100 text-amber-700';
  if (t?.includes('PUBLISH')) return 'bg-emerald-100 text-emerald-700';
  if (t?.includes('BULK')) return 'bg-blue-100 text-blue-700';
  if (t?.includes('DELETE')) return 'bg-rose-100 text-rose-700';
  return 'bg-slate-100 text-slate-700';
};
</script>
