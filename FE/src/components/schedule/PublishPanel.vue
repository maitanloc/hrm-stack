<template>
  <div class="max-w-2xl mx-auto space-y-8 py-8">
    <div class="text-center">
       <div class="w-20 h-20 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-indigo-100">
          <span class="material-symbols-outlined text-4xl">fact_check</span>
       </div>
       <h3 class="text-2xl font-bold text-slate-900 tracking-tight">Kiểm tra & Chốt lịch phân ca</h3>
       <p class="text-slate-500 text-sm mt-2 max-w-sm mx-auto">Vui lòng hoàn thành các bước rà soát dưới đây trước khi công bố lịch chính thức cho nhân sự.</p>
    </div>

    <!-- Ready Checklists -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
       <div v-for="(check, idx) in checklist" :key="idx" 
            class="p-5 border-b border-slate-100 last:border-b-0 flex items-center justify-between group transition-all"
            :class="check.status ? 'bg-emerald-50/20' : 'bg-slate-50/50'">
          <div class="flex items-center gap-4">
             <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 border"
                  :class="check.status ? 'bg-emerald-100 text-emerald-600 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'">
                <span class="material-symbols-outlined text-[20px]">{{ check.status ? 'check' : 'pending' }}</span>
             </div>
             <div>
                <p class="text-sm font-bold" :class="check.status ? 'text-emerald-900' : 'text-slate-700'">{{ check.label }}</p>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-1">{{ check.desc }}</p>
             </div>
          </div>
          <button v-if="!check.status" @click="check.action" class="text-xs font-black text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1.5 rounded-lg transition-colors">
            XỬ LÝ NGAY
          </button>
       </div>
    </div>

    <!-- Published State -->
    <div v-if="store.isPublished" class="bg-emerald-50 border-2 border-emerald-200 p-8 rounded-2xl flex flex-col items-center gap-4 text-center shadow-sm">
       <span class="material-symbols-outlined text-emerald-500 text-5xl">verified</span>
       <div>
          <p class="text-emerald-900 font-black text-xl tracking-tight">Lịch tuần này đã được công bố!</p>
          <p class="text-emerald-700 text-sm mt-2 font-medium">Bởi: <span class="font-bold">{{ store.publishedBy || 'Hệ thống' }}</span> vào lúc {{ formatDate(store.publishedAt) }}</p>
       </div>
       <div class="mt-4 p-4 bg-white/60 rounded-xl border border-emerald-100/50 max-w-sm">
          <p class="text-xs text-emerald-800 font-medium">Lịch nền đã bị khóa. Bất kỳ thay đổi nào từ bây giờ phải được thực hiện thông qua Tab <strong>Ngoại lệ (Override)</strong>.</p>
       </div>
    </div>

    <!-- Publish Action -->
    <div v-else class="pt-4 flex flex-col items-center justify-center gap-4">
       <button 
         @click="handlePublish"
         :disabled="store.submitting || !isReadyToPublish"
         class="w-full max-w-xs py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black text-lg shadow-xl shadow-indigo-200 transition-all active:scale-95 disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-3"
       >
         <span v-if="store.submitting" class="material-symbols-outlined animate-spin">refresh</span>
         <span v-else class="material-symbols-outlined">send</span>
         PUBLISH NGAY
       </button>
       
       <p v-if="!isReadyToPublish && !store.isPublished" class="text-center text-xs text-rose-500 font-bold bg-rose-50 px-4 py-2 rounded-lg border border-rose-100">
          <span class="material-symbols-outlined text-[14px] align-middle mr-1">warning</span>
          Bạn cần phân ca đầy đủ cho tất cả nhân viên trước khi công bố.
       </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const checklist = computed(() => [
  { 
    label: 'Danh sách nhân sự', 
    desc: 'Tất cả nhân viên đã được định danh',
    status: store.totalEmployees > 0,
    action: () => store.setActiveTab(1)
  },
  { 
    label: 'Phân ca đầy đủ', 
    desc: store.unassignedEmployees === 0 ? 'Tất cả nhân sự đã có ca trực' : `Còn ${store.unassignedEmployees} nhân sự thiếu lịch trực`,
    status: store.unassignedEmployees === 0 && store.totalEmployees > 0,
    action: () => store.setActiveTab(3) // Redirect to warning tab
  },
  { 
    label: 'Tuân thủ chính sách', 
    desc: store.warningCount === 0 ? 'Không phát hiện vi phạm' : `${store.warningCount} cảnh báo cần xem xét`,
    status: store.warningCount === 0,
    action: () => store.setActiveTab(3)
  }
]);

const isReadyToPublish = computed(() => {
  return store.unassignedEmployees === 0 && store.totalEmployees > 0;
});

const formatDate = (date) => date ? new Date(date).toLocaleString('vi-VN') : '';

const handlePublish = async () => {
  if (store.warningCount > 0) {
    store.openModal('blockPublish', { 
       title: 'Vẫn còn cảnh báo vi phạm',
       message: 'Hệ thống phát hiện các rủi ro về thời gian làm việc hoặc trùng lịch nghỉ phép. Bạn có muốn tiếp tục công bố lịch này không?'
    });
  } else {
    store.openModal('confirmAction', {
       type: 'publish',
       title: 'Xác nhận Công bố',
       message: 'Sau khi công bố, lịch nền sẽ bị khóa và không thể chỉnh sửa hàng loạt. Bất kỳ thay đổi nào cũng phải thông qua Ghi đè (Override). Bạn đã kiểm tra kỹ chưa?'
    });
  }
};
</script>
