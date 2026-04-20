<template>
  <div class="px-6 py-6 bg-[#F8FAFC] min-h-screen">
    <div class="mb-6 border-b border-slate-200 pb-6">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
        <span class="material-symbols-outlined text-indigo-600">settings_applications</span>
        Policy & Workflow Center
      </h1>
      <p class="text-slate-500 text-sm mt-1 max-w-2xl">Cấu hình các quy tắc nghiệp vụ, chính sách thời gian và luồng phê duyệt mà không cần can thiệp mã nguồn.</p>
    </div>
    
    <div v-if="loading" class="py-20 text-center bg-white rounded-2xl border border-slate-200 shadow-sm animate-pulse">
        <div class="w-12 h-12 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin mx-auto mb-4"></div>
        <span class="text-slate-400 font-bold text-sm uppercase tracking-widest">Đang tải cấu hình hệ thống...</span>
    </div>
    
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
       <!-- Time Policy Form -->
       <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
           <div class="px-6 py-4 bg-indigo-50/50 border-b border-indigo-100 font-bold text-indigo-900 flex items-center gap-2">
               <span class="material-symbols-outlined text-[20px]">schedule</span>
               1. Chính sách Thời gian & Chấm công
           </div>
           <div class="p-6 space-y-6 flex-1">
              <div>
                  <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Thời gian du di (Grace Period - phút)</label>
                  <input type="number" v-model.number="forms.grace_period_minutes" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none p-3 border transition-all font-bold text-slate-700" />
                  <p class="text-[10px] text-slate-400 mt-2 italic font-medium">Số phút cho phép đi muộn hoặc về sớm mà không bị trừ công.</p>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                      <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Ngưỡng nửa công (phút)</label>
                      <input type="number" v-model.number="forms.half_day_threshold_minutes" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none p-3 border transition-all font-bold text-slate-700" />
                  </div>
                  <div>
                      <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Định mức full công (phút)</label>
                      <input type="number" v-model.number="forms.full_day_minutes" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none p-3 border transition-all font-bold text-slate-700" />
                  </div>
              </div>
           </div>
           <div class="p-6 pt-0 flex justify-end items-center bg-slate-50/30 border-t border-slate-50 mt-auto">
                <span v-if="saved.time" class="text-xs text-emerald-600 mr-4 font-bold animate-bounce flex items-center gap-1">
                  <span class="material-symbols-outlined text-[16px]">check_circle</span> Đã lưu cấu hình!
                </span>
                <button @click="saveConfig('time')" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-100 transition active:scale-95">LƯU CẤU HÌNH</button>
           </div>
       </div>
       
       <!-- Workflow Rules -->
       <div class="space-y-6 flex flex-col">
           <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
               <div class="px-6 py-4 bg-amber-50/50 border-b border-amber-100 font-bold text-amber-900 flex items-center gap-2">
                   <span class="material-symbols-outlined text-[20px]">verified_user</span>
                   2. Quy tắc Publish Lịch Làm Việc
               </div>
               <div class="p-6">
                  <div class="flex items-start gap-4 p-4 rounded-xl border-2 transition-all cursor-pointer" 
                       :class="forms.strict_schedule_publish ? 'border-amber-200 bg-amber-50/30' : 'border-slate-100 bg-slate-50/30'"
                       @click="forms.strict_schedule_publish = !forms.strict_schedule_publish">
                     <div class="flex items-center h-6 shrink-0">
                       <div class="w-10 h-6 rounded-full relative transition-colors duration-200 flex items-center px-1"
                            :class="forms.strict_schedule_publish ? 'bg-amber-500' : 'bg-slate-300'">
                          <div class="w-4 h-4 bg-white rounded-full transition-transform duration-200 shadow-sm"
                               :class="forms.strict_schedule_publish ? 'translate-x-4' : 'translate-x-0'"></div>
                       </div>
                     </div>
                     <div class="text-sm">
                       <p class="font-bold text-slate-800 uppercase text-xs tracking-tight mb-1">Strict Mode (Kiểm soát chặt chẽ)</p>
                       <p class="text-slate-500 text-xs leading-relaxed">Nếu BẬT: Quản lý tuyệt đối không thể publish lịch nếu phát hiện trùng lặp với lịch nghỉ phép hoặc có ca trực rỗng. <br/><span class="text-amber-600 font-bold text-[10px]">* Khuyên dùng cho môi trường sản xuất.</span></p>
                     </div>
                  </div>
                  
                  <div class="mt-6 flex justify-end">
                       <span v-if="saved.workflow" class="text-xs text-emerald-600 mr-4 font-bold animate-bounce flex items-center gap-1">
                         <span class="material-symbols-outlined text-[16px]">check_circle</span> Đã cập nhật!
                       </span>
                       <button @click="saveConfig('workflow')" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-amber-100 transition active:scale-95">CẬP NHẬT QUY TẮC</button>
                  </div>
               </div>
           </div>
           
           <div class="bg-slate-900 rounded-2xl shadow-xl border border-slate-800 p-6 text-white relative overflow-hidden">
               <div class="relative z-10">
                 <div class="flex items-center gap-2 mb-4 text-rose-400 font-bold uppercase text-[10px] tracking-widest">
                   <span class="material-symbols-outlined text-[16px]">lock</span>
                   Hệ thống Workflow tự động
                 </div>
                 <h4 class="font-black text-lg mb-2">Khóa chu kỳ & Payroll Lock</h4>
                 <p class="text-slate-400 text-sm leading-relaxed mb-4">Các thao tác sửa công, chèn log chấm công sẽ bị khóa hoàn toàn khi Chu kỳ tính lương (Salary Period) được chuyển sang trạng thái <span class="text-emerald-400 font-bold">CLOSED</span>.</p>
                 <div class="inline-flex items-center px-3 py-1 bg-slate-800 rounded-lg border border-slate-700 text-[10px] font-bold text-slate-300 italic">
                   Quản lý bởi WorkflowTransitionGuard v1.4
                 </div>
               </div>
               <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-9xl text-white/5 pointer-events-none">security</span>
           </div>
       </div>
       
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { apiRequest } from '@/services/beApi.js'

const loading = ref(true)
const saved = reactive({ time: false, workflow: false })

const forms = reactive({
    grace_period_minutes: 5,
    half_day_threshold_minutes: 240,
    full_day_minutes: 480,
    strict_schedule_publish: true
})

onMounted(async () => {
    try {
        const res = await apiRequest('/settings/general');
        if(res && res.data) {
             const d = res.data;
             if(d.grace_period_minutes !== undefined) forms.grace_period_minutes = Number(d.grace_period_minutes);
             if(d.half_day_threshold_minutes !== undefined) forms.half_day_threshold_minutes = Number(d.half_day_threshold_minutes);
             if(d.full_day_minutes !== undefined) forms.full_day_minutes = Number(d.full_day_minutes);
             if(d.strict_schedule_publish !== undefined) forms.strict_schedule_publish = d.strict_schedule_publish === 'true' || d.strict_schedule_publish === '1';
        }
    } catch (e) {
        console.error("Failed to load settings:", e);
    } finally {
        setTimeout(() => { loading.value = false; }, 400); // Small delay for smooth feel
    }
})

const saveConfig = async (group) => {
    try {
        let payload = {};
        if (group === 'time') {
            payload = {
                grace_period_minutes: String(forms.grace_period_minutes),
                half_day_threshold_minutes: String(forms.half_day_threshold_minutes),
                full_day_minutes: String(forms.full_day_minutes)
            }
        } else if (group === 'workflow') {
            payload = {
                strict_schedule_publish: forms.strict_schedule_publish ? '1' : '0'
            }
        }
        
        await apiRequest('/settings/general', {
            method: 'PUT',
            body: payload
        });
        saved[group] = true;
        setTimeout(() => saved[group] = false, 3000);
    } catch (e) {
        console.error("Save config error:", e);
        alert("Lỗi khi lưu cấu hình: " + (e.message || "Unknown error"));
    }
}
</script>
