<template>
  <div class="px-6 py-6">
    <div class="mb-6 border-b border-gray-200 pb-4">
      <h1 class="text-2xl font-bold text-gray-900 border-l-4 border-indigo-600 pl-3">Policy & Workflow Configuration Center</h1>
      <p class="text-sm text-gray-500 mt-2 pl-4">Quản lý linh hoạt các quy tắc, chính sách, hệ số chấm công và mức độ kiểm duyệt của hệ thống mà không cần sửa code.</p>
    </div>
    
    <div v-if="loading" class="py-10 text-center">
        <span class="text-gray-500">Đang tải cấu hình...</span>
    </div>
    
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
       <!-- Time Policy Form -->
       <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
           <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100 font-semibold text-indigo-900">
               1. Chính sách Thời gian & Chấm công
           </div>
           <div class="p-6 space-y-4">
              <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian du di (Grace Period - phút)</label>
                  <input type="number" v-model.number="forms.grace_period_minutes" class="w-full border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border shadow-sm" />
                  <p class="text-xs text-gray-500 mt-1">Số phút đi muộn hoặc về sớm cho phép không bị trừ công.</p>
              </div>
              
              <hr class="border-gray-100 my-4" />
              
              <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Ngưỡng nửa công (phút làm việc tối thiểu)</label>
                  <input type="number" v-model.number="forms.half_day_threshold_minutes" class="w-full border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border shadow-sm" />
                  <p class="text-xs text-gray-500 mt-1">Làm dưới mức này sẽ bị coi là nghỉ cả ngày. (Thường: 240p = 4 tiếng)</p>
              </div>
              
              <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Định mức full công (phút làm việc tối thiểu)</label>
                  <input type="number" v-model.number="forms.full_day_minutes" class="w-full border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 p-2 border shadow-sm" />
                  <p class="text-xs text-gray-500 mt-1">Làm đủ mức này mới được tính 1 công đầy đủ. (Thường: 480p = 8 tiếng)</p>
              </div>
              
              <div class="pt-4 flex justify-end items-center">
                  <span v-if="saved.time" class="text-sm text-green-600 mr-4 font-medium transition duration-500">Đã lưu!</span>
                  <button @click="saveConfig('time')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-medium shadow-sm transition">Lưu cấu hình thời gian</button>
              </div>
           </div>
       </div>
       
       <!-- Workflow Audit & Rule Forms -->
       <div class="space-y-6">
           <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
               <div class="px-6 py-4 bg-orange-50 border-b border-orange-100 font-semibold text-orange-900">
                   2. Quy tắc Publish Lịch Làm Việc
               </div>
               <div class="p-6 space-y-4">
                  <div class="flex items-start">
                     <div class="flex items-center h-5">
                       <input id="strict_mode" type="checkbox" v-model="forms.strict_schedule_publish" class="focus:ring-orange-500 h-4 w-4 text-orange-600 border-gray-300 rounded">
                     </div>
                     <div class="ml-3 text-sm">
                       <label for="strict_mode" class="font-medium text-gray-700">Strict Mode (Bắt buộc sửa hết lỗi trước khi Publish)</label>
                       <p class="text-gray-500 mt-1">Nếu BẬT: Quản lý không thể publish lịch nếu phát hiện trùng lặp với lịch nghỉ phép hoặc có ca trực rỗng. Sẽ block hoàn toàn.<br/> Nếu TẮT: Hệ thống chỉ cảnh báo và vẫn cho phép publish.</p>
                     </div>
                  </div>
                  
                  <div class="block pt-4 text-right">
                       <span v-if="saved.workflow" class="text-sm text-green-600 mr-4 font-medium">Đã lưu!</span>
                       <button @click="saveConfig('workflow')" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded font-medium shadow-sm transition">Cập nhật quy tắc Publish</button>
                  </div>
               </div>
           </div>
           
           <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
               <div class="px-6 py-4 bg-red-50 border-b border-red-100 font-semibold text-red-900 flex justify-between">
                   <span>3. Khóa chu kỳ & Payroll Lock</span>
               </div>
               <div class="p-6">
                  <p class="text-sm text-gray-600 mb-4">Các thao tác sửa công, chèn log chấm công sẽ bị khóa hoàn toàn khi Chu kỳ tính lương (Salary Period) được chuyển sang trạng thái CLOSED.</p>
                  <p class="text-xs text-gray-500 italic block border-l-2 border-red-300 pl-2">Tính năng này chạy tự động thông qua WorkflowTransitionGuard của hệ thống tại Backend.</p>
               </div>
           </div>
       </div>
       
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { apiRequest as requestApi } from '@/services/beApi.js'


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
        const res = await requestApi('/settings/general', 'GET');
        if(res && res.data) {
             const d = res.data;
             if(d.grace_period_minutes !== undefined) forms.grace_period_minutes = Number(d.grace_period_minutes);
             if(d.half_day_threshold_minutes !== undefined) forms.half_day_threshold_minutes = Number(d.half_day_threshold_minutes);
             if(d.full_day_minutes !== undefined) forms.full_day_minutes = Number(d.full_day_minutes);
             if(d.strict_schedule_publish !== undefined) forms.strict_schedule_publish = d.strict_schedule_publish === 'true' || d.strict_schedule_publish === '1';
        }
    } catch (e) {
        console.error("Failed to load generic settings", e);
    } finally {
        loading.value = false;
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
        
        await requestApi('/settings/general', 'PUT', payload);
        saved[group] = true;
        setTimeout(() => saved[group] = false, 3000);
    } catch (e) {
        alert();
    }
}
</script>
