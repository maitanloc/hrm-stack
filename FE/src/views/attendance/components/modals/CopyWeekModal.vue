<template>
  <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity duration-500" @click="close"></div>
    <div class="relative w-full max-w-lg bg-[var(--sys-bg-surface)] rounded-[48px] shadow-2xl border border-[var(--sys-border-subtle)] animation-pop-in overflow-hidden">
      
      <!-- State 1: Selection & Brief -->
      <template v-if="!showPreview">
        <div class="p-10 text-center space-y-6">
          <div class="w-20 h-20 rounded-[32px] bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center mx-auto border border-[var(--sys-brand-border)] shadow-xl shadow-[var(--sys-brand-solid)]/10">
            <span class="material-symbols-rounded text-[40px]">content_copy</span>
          </div>
          <div class="space-y-2">
            <h3 class="text-2xl font-black tracking-tight text-[var(--sys-text-primary)] uppercase">Sao chép lịch tuần</h3>
            <p class="text-[14px] text-[var(--sys-text-secondary)] leading-relaxed font-medium">
              Sử dụng dữ liệu phân ca từ tuần trước để làm mới lịch hiện tại một cách nhanh chóng.
            </p>
          </div>

          <div class="p-6 rounded-[32px] bg-[var(--sys-bg-page)]/80 border border-[var(--sys-border-subtle)] space-y-4 relative overflow-hidden group">
             <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:scale-110 transition-transform duration-1000">
                <span class="material-symbols-rounded text-7xl">history</span>
             </div>
             <div class="flex items-center justify-between text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-[0.15em]">
                <span>Nguồn (Trước)</span>
                <span>Đích (Nay)</span>
             </div>
             <div class="flex items-center justify-between px-2">
                <div class="flex flex-col items-start gap-1">
                   <span class="text-[15px] font-black text-slate-700">{{ prevWeekRange }}</span>
                   <span class="text-[9px] font-bold text-slate-400 uppercase">Giai đoạn cũ</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white border border-slate-100 flex items-center justify-center shadow-sm">
                   <span class="material-symbols-rounded text-[var(--sys-brand-solid)] text-[24px]">forward</span>
                </div>
                <div class="flex flex-col items-end gap-1">
                   <span class="text-[15px] font-black text-[var(--sys-brand-solid)]">{{ currentWeekRange }}</span>
                   <span class="text-[9px] font-bold text-[var(--sys-brand-solid)] opacity-60 uppercase">Tuần hiện tại</span>
                </div>
             </div>
          </div>
        </div>

        <div class="px-10 py-8 bg-[var(--sys-bg-page)]/80 border-t border-[var(--sys-border-subtle)] flex items-center justify-end gap-4 backdrop-blur-sm">
          <button @click="close" class="px-8 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">Hủy bỏ</button>
          <button 
            @click="showPreview = true"
            class="px-10 py-4 rounded-2xl bg-[var(--sys-brand-solid)] text-white text-[12px] font-black uppercase tracking-widest hover:brightness-110 active:scale-95 transition-all shadow-xl shadow-[var(--sys-brand-solid)]/30 flex items-center gap-2"
          >
            TIẾP TỤC XEM TRƯỚC
            <span class="material-symbols-rounded text-[18px]">chevron_right</span>
          </button>
        </div>
      </template>

      <!-- State 2: Preview Diff (Phase 3) -->
      <template v-else>
         <div class="p-10 space-y-8 animate-in slide-in-from-right-4 duration-500">
            <div class="flex items-center gap-4">
               <button @click="showPreview = false" class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 hover:text-[var(--sys-brand-solid)] transition-all">
                  <span class="material-symbols-rounded text-[20px]">arrow_back</span>
               </button>
               <div>
                 <h3 class="text-xl font-black tracking-tight text-[var(--sys-text-primary)]">Kết quả dự kiến</h3>
                 <p class="text-[12px] text-[var(--sys-text-secondary)] font-medium">Hệ thống đã phân tích các thay đổi sắp thực hiện.</p>
               </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
               <div class="p-5 rounded-[28px] bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] shadow-sm">
                  <span class="text-[9px] font-black uppercase tracking-widest text-[var(--sys-text-disabled)] block mb-1">Dữ liệu mới</span>
                  <span class="text-2xl font-black text-slate-700">{{ store.totalEmployees * 7 }} <span class="text-[10px] font-bold text-slate-400">ô</span></span>
               </div>
               <div class="p-5 rounded-[28px] bg-amber-50 border border-amber-100 shadow-sm">
                  <span class="text-[9px] font-black uppercase tracking-widest text-amber-500 block mb-1">Đã có ghi đè</span>
                  <span class="text-2xl font-black text-amber-600">{{ store.overrides.length }} <span class="text-[10px] font-bold text-amber-400">ô</span></span>
               </div>
            </div>

            <div class="p-6 rounded-[32px] bg-rose-50 border border-rose-100 flex items-start gap-4">
               <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-500 flex items-center justify-center shrink-0">
                 <span class="material-symbols-rounded text-[24px]">verified_user</span>
               </div>
               <div class="space-y-1">
                  <p class="text-[11px] font-black text-rose-600 uppercase tracking-widest">Quy tắc bảo toàn</p>
                  <p class="text-[12px] text-rose-500 leading-relaxed font-medium">
                     Lịch tuần trước sẽ được điền vào các ô đang trống. Các ô đã có <strong>Ghi đè</strong> hoặc đã được gán thủ công trong tuần này sẽ được giữ nguyên.
                  </p>
               </div>
            </div>

            <div class="max-h-32 overflow-y-auto custom-scrollbar pr-2 space-y-2">
               <div class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 bg-slate-50/50" v-for="i in 2" :key="i">
                  <div class="flex items-center gap-3">
                     <div class="w-2 h-2 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></div>
                     <span class="text-[13px] font-bold text-slate-700">Mẫu kiểm tra dữ liệu #{{ i }}</span>
                  </div>
                  <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest bg-emerald-50 px-2 py-0.5 rounded-full">Sẵn sàng</span>
               </div>
            </div>
         </div>

         <div class="px-10 py-8 bg-[var(--sys-bg-page)]/80 border-t border-[var(--sys-border-subtle)] backdrop-blur-sm">
            <button 
              @click="handleCopy"
              :disabled="store.submitting"
              class="w-full py-5 rounded-[32px] bg-[var(--sys-brand-solid)] text-white text-[13px] font-black uppercase tracking-[0.15em] hover:brightness-110 active:scale-95 transition-all shadow-2xl shadow-[var(--sys-brand-solid)]/30 disabled:opacity-50 disabled:grayscale disabled:shadow-none"
            >
              <div class="flex items-center justify-center gap-3">
                 <span v-if="store.submitting" class="material-symbols-rounded animate-spin text-[20px]">sync</span>
                 <span v-else class="material-symbols-rounded text-[20px]">auto_mode</span>
                 {{ store.submitting ? 'ĐANG SAO CHÉP...' : 'XÁC NHẬN & SAO CHÉP' }}
              </div>
            </button>
         </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const showPreview = ref(false);

const close = () => {
  showPreview.value = false;
  store.closeModal('copyWeek');
};

const formatDateRange = computed(() => {
  return `${store.selectedDateRange.from} - ${store.selectedDateRange.to}`;
});

const currentWeekRange = computed(() => {
  if (!store.selectedDateRange.from) return '---';
  const d = new Date(store.selectedDateRange.from);
  return `${d.getDate()}/${d.getMonth()+1}`;
});

const prevWeekRange = computed(() => {
  if (!store.selectedDateRange.from) return '---';
  const d = new Date(store.selectedDateRange.from);
  d.setDate(d.getDate() - 7);
  return `${d.getDate()}/${d.getMonth()+1}`;
});

const handleCopy = async () => {
  try {
    await store.copyScheduleWeek(store.selectedDateRange.from, store.selectedDateRange.to);
    alert('Thành công! Lịch tuần trước đã được sao chép.');
    close();
  } catch (err) {
    alert('Lỗi: ' + err.message);
  }
};
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
