<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
       <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
         <span class="material-symbols-outlined text-rose-500">warning</span>
         Cảnh báo vi phạm (Minimal)
       </h3>
       <div class="flex items-center gap-2">
         <span class="text-xs font-bold text-slate-400">TỔNG LỖI:</span>
         <span class="bg-rose-100 text-rose-700 px-3 py-1 rounded-full text-sm font-black shadow-sm border border-rose-200">{{ store.warningCount }}</span>
       </div>
    </div>

    <!-- Empty State -->
    <div v-if="store.warningCount === 0" class="bg-emerald-50 border border-emerald-100 p-16 rounded-2xl text-center shadow-sm">
       <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-sm">
         <span class="material-symbols-outlined text-emerald-500 text-4xl">check_circle</span>
       </div>
       <p class="text-emerald-900 font-bold text-xl">Lịch trình hoàn hảo!</p>
       <p class="text-emerald-700 text-sm mt-2">Không phát hiện vi phạm nào trong tuần này. Lịch đã sẵn sàng để Publish.</p>
    </div>

    <!-- Warnings List -->
    <div v-else class="space-y-4">
       <!-- Unassigned -->
       <div v-if="store.warnings?.unassigned?.length" class="space-y-3">
         <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest pl-2">Thiếu ca trực ({{store.warnings.unassigned.length}})</h4>
         <div v-for="(w, idx) in store.warnings.unassigned" :key="'un_'+idx" 
              class="bg-white p-4 rounded-xl border-l-4 border-amber-500 shadow-sm border border-slate-200 flex items-start gap-4 hover:shadow-md transition-all">
            <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center shrink-0 border border-amber-100">
               <span class="material-symbols-outlined text-amber-600 text-[20px]">event_busy</span>
            </div>
            <div class="flex-1">
               <div class="flex flex-wrap items-center gap-2 mb-1">
                  <span class="text-sm font-black text-slate-800 uppercase">{{ w.employee?.full_name || 'Không xác định' }}</span>
                  <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-black border border-slate-200">{{ formatDate(w.work_date) }}</span>
               </div>
               <p class="text-sm text-slate-600 leading-relaxed font-medium">Nhân sự chưa được gán ca làm việc hoặc lý do vắng mặt.</p>
            </div>
         </div>
       </div>

       <!-- Leave Conflicts -->
       <div v-if="store.warnings?.leave_conflicts?.length" class="space-y-3">
         <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest pl-2 mt-4">Trùng lịch nghỉ phép ({{store.warnings.leave_conflicts.length}})</h4>
         <div v-for="(w, idx) in store.warnings.leave_conflicts" :key="'lc_'+idx" 
              class="bg-white p-4 rounded-xl border-l-4 border-rose-500 shadow-sm border border-slate-200 flex items-start gap-4 hover:shadow-md transition-all">
            <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center shrink-0 border border-rose-100">
               <span class="material-symbols-outlined text-rose-600 text-[20px]">free_cancellation</span>
            </div>
            <div class="flex-1">
               <div class="flex flex-wrap items-center gap-2 mb-1">
                  <span class="text-sm font-black text-slate-800 uppercase">{{ w.employee?.full_name }}</span>
                  <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-black border border-slate-200">{{ formatDate(w.work_date) }}</span>
               </div>
               <p class="text-sm text-slate-600 leading-relaxed font-medium">Lịch làm việc đang xung đột với đơn nghỉ phép đã được duyệt.</p>
            </div>
         </div>
       </div>

       <!-- Late/Overtime Risks (Minimal UI for these) -->
       <div v-if="store.warnings?.late_risk?.length || store.warnings?.overtime_risk?.length" class="space-y-3">
         <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest pl-2 mt-4">Rủi ro chấm công ({{(store.warnings?.late_risk?.length||0) + (store.warnings?.overtime_risk?.length||0)}})</h4>
         <div v-for="(w, idx) in [...(store.warnings?.late_risk||[]), ...(store.warnings?.overtime_risk||[])]" :key="'risk_'+idx" 
              class="bg-white p-4 rounded-xl border-l-4 border-indigo-500 shadow-sm border border-slate-200 flex items-start gap-4 hover:shadow-md transition-all">
            <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100">
               <span class="material-symbols-outlined text-indigo-600 text-[20px]">timelapse</span>
            </div>
            <div class="flex-1">
               <div class="flex flex-wrap items-center gap-2 mb-1">
                  <span class="text-sm font-black text-slate-800 uppercase">{{ w.employee?.full_name }}</span>
                  <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-black border border-slate-200">{{ formatDate(w.work_date) }}</span>
               </div>
               <p class="text-sm text-slate-600 leading-relaxed font-medium">
                  {{ w.late_minutes ? `Cảnh báo đi muộn: ${w.late_minutes} phút.` : `Cảnh báo OT quá mức: ${w.overtime_minutes} phút.` }}
               </p>
            </div>
         </div>
       </div>
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};
</script>
