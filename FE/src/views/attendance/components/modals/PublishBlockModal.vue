<template>
  <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="close"></div>
    <div class="relative w-full max-w-md bg-[var(--sys-bg-surface)] rounded-[40px] shadow-2xl border border-[var(--sys-border-subtle)] animation-pop-in">
      
      <div class="p-8 text-center space-y-4">
        <div class="w-16 h-16 rounded-[24px] bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] flex items-center justify-center mx-auto border border-[var(--sys-danger-border)]">
          <span class="material-symbols-rounded text-[32px]">block</span>
        </div>
        <div>
          <h3 class="text-xl font-black tracking-tight">Không thể công bố</h3>
          <p class="text-sm text-[var(--sys-text-secondary)] leading-relaxed mt-2">
            Lịch làm việc của bạn chưa đủ điều kiện để công bố. Vui lòng rà soát lại các mục sau:
          </p>
        </div>

        <div class="space-y-2 text-left mt-6">
           <div 
             v-for="(error, i) in errors" 
             :key="i"
             class="p-4 rounded-2xl bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] text-xs font-bold border border-[var(--sys-danger-border)] flex items-start gap-3"
           >
              <span class="material-symbols-rounded text-[18px] shrink-0">error_outline</span>
              <span>{{ error }}</span>
           </div>
        </div>
      </div>

      <div class="px-8 py-6 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] flex items-center justify-end">
        <button @click="close" class="w-full py-4 rounded-2xl bg-[var(--sys-bg-hover)] text-[var(--sys-text-primary)] text-xs font-black uppercase tracking-widest hover:brightness-95 transition-all">
          ĐÃ HIỂU
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const close = () => store.closeModal('blockPublish');

const errors = computed(() => {
  const list = [];
  if (store.unassignedEmployees > 0) list.push(`${store.unassignedEmployees} nhân sự chưa được gán ca.`);
  if (store.allWarnings.some(w => w.severity === 'ERROR')) list.push('Vẫn còn lỗi xung đột nghiêm trọng chưa xử lý.');
  
  const customMessage = store.modals.blockPublish?.data?.message;
  if (customMessage) list.push(customMessage);
  
  return list.length > 0 ? list : ['Yêu cầu hệ thống không xác định.'];
});
</script>

<style scoped>
@keyframes pop-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animation-pop-in {
  animation: pop-in 0.3s cubic-bezier(0.2, 0.8, 0.3, 1);
}
</style>
