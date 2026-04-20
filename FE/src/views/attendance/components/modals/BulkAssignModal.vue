<template>
  <div class="fixed inset-0 z-[100] flex justify-end">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity duration-500" @click="close"></div>
    <div class="relative w-full max-w-md h-full bg-[var(--sys-bg-surface)] shadow-2xl flex flex-col border-l border-[var(--sys-border-subtle)] animation-slide-left">
      
      <!-- Drawer Header -->
      <div class="p-10 border-b border-[var(--sys-border-subtle)] bg-gradient-to-br from-[var(--sys-bg-page)]/50 to-transparent">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 rounded-2xl bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)] shadow-lg shadow-[var(--sys-brand-solid)]/10">
            <span class="material-symbols-rounded text-[28px]">bolt</span>
          </div>
          <button @click="close" class="w-12 h-12 rounded-2xl flex items-center justify-center bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all active:scale-90">
            <span class="material-symbols-rounded">close</span>
          </button>
        </div>
        <h3 class="text-2xl font-black tracking-tight text-[var(--sys-text-primary)] uppercase">Gán ca hàng loạt</h3>
        <p class="text-[13px] text-[var(--sys-text-secondary)] mt-2 leading-relaxed font-medium">Thiết lập nhanh lịch làm việc cơ sở cho nhóm nhân sự trong đơn vị.</p>
      </div>

      <!-- Drawer Content -->
      <div class="flex-1 overflow-y-auto p-10 space-y-10 custom-scrollbar">
        
        <!-- Summary Info Card -->
        <div class="p-6 rounded-[32px] bg-[var(--sys-brand-soft)]/50 border border-[var(--sys-brand-border)] relative overflow-hidden group">
          <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-700">
            <span class="material-symbols-rounded text-6xl">groups</span>
          </div>
          <div class="relative space-y-3">
            <div class="flex items-center gap-2.5">
               <span class="w-2 h-2 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></span>
               <span class="text-[10px] font-black uppercase tracking-widest text-[var(--sys-brand-solid)]">Phạm vi áp dụng</span>
            </div>
            <div class="space-y-1">
              <p class="text-[15px] font-bold text-[var(--sys-text-primary)]">Lịch gán cho: {{ store.totalEmployees }} nhân sự</p>
              <div class="flex items-center gap-2 text-[12px] text-[var(--sys-text-secondary)] font-medium">
                <span class="material-symbols-rounded text-[16px]">calendar_today</span>
                <span>Tuần: {{ formatDateRange }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Inputs Section -->
        <div class="space-y-8">
          <!-- Shift Selection -->
          <div class="space-y-4">
            <label class="text-[10px] font-black uppercase tracking-[0.25em] text-[var(--sys-text-secondary)] opacity-50 px-2">Ca làm việc mục tiêu</label>
            <div class="relative group">
              <select v-model="form.shift_type_id" class="w-full px-6 py-4.5 rounded-[24px] bg-[var(--sys-bg-page)] border-2 border-transparent focus:border-[var(--sys-brand-solid)] outline-none transition-all duration-500 text-[14px] font-bold appearance-none shadow-inner">
                 <option :value="null">-- Chọn loại ca --</option>
                 <option v-for="s in store.shifts" :key="s.shift_type_id" :value="s.shift_type_id">
                   {{ s.shift_code }} ({{ s.start_time?.slice(0,5) }} - {{ s.end_time?.slice(0,5) }})
                 </option>
              </select>
              <span class="material-symbols-rounded absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-[var(--sys-brand-solid)] transition-colors">expand_more</span>
            </div>
          </div>

          <!-- Options Selection -->
          <div class="space-y-4">
             <label class="text-[10px] font-black uppercase tracking-[0.25em] text-[var(--sys-text-secondary)] opacity-50 px-2">Cấu hình nâng cao</label>
             <div class="flex flex-col gap-4">
                <label class="flex items-start gap-4 p-5 rounded-[28px] bg-[var(--sys-bg-page)] border-2 border-transparent hover:border-[var(--sys-border-subtle)] cursor-pointer transition-all duration-300 group/opt">
                  <div class="relative mt-1">
                    <input type="checkbox" v-model="form.override_existing" class="peer appearance-none w-6 h-6 rounded-lg border-2 border-slate-300 checked:bg-[var(--sys-brand-solid)] checked:border-[var(--sys-brand-solid)] transition-all cursor-pointer" />
                    <span class="material-symbols-rounded absolute inset-0 text-white text-[18px] flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none">check</span>
                  </div>
                  <div class="flex flex-col space-y-0.5">
                    <span class="text-[14px] font-bold text-[var(--sys-text-primary)] group-hover/opt:text-[var(--sys-brand-solid)] transition-colors">Ghi đè lịch đã có</span>
                    <span class="text-[11px] text-[var(--sys-text-secondary)] font-medium leading-relaxed opacity-60">Cho phép hệ thống làm mới hoàn toàn các ca đã gán trước đó trong giai đoạn này.</span>
                  </div>
                </label>
             </div>
          </div>
        </div>

        <!-- Warning Alert -->
        <div class="p-6 rounded-[32px] bg-amber-50 border border-amber-100 flex gap-4 animate-in slide-in-from-bottom-2 duration-700">
           <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
             <span class="material-symbols-rounded text-[24px]">warning</span>
           </div>
           <div class="space-y-1">
             <p class="text-[11px] font-black text-amber-700 uppercase tracking-widest">Xác nhận thao tác</p>
             <p class="text-[12px] text-amber-600 font-medium leading-relaxed">Hành động này sẽ tạo hàng loạt bản ghi lịch làm việc. Vui lòng kiểm tra kỹ phạm vi nhân sự trước khi thực hiện.</p>
           </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="p-10 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/80 backdrop-blur-sm">
        <button 
          @click="handleApply"
          :disabled="!form.shift_type_id || store.submitting"
          class="w-full py-6 rounded-[32px] bg-[var(--sys-brand-solid)] text-white font-black text-[14px] uppercase tracking-[0.15em] hover:brightness-110 active:scale-95 transition-all shadow-2xl shadow-[var(--sys-brand-solid)]/30 disabled:opacity-50 disabled:grayscale disabled:shadow-none"
        >
          <div class="flex items-center justify-center gap-3">
             <span v-if="store.submitting" class="material-symbols-rounded animate-spin text-[20px]">sync</span>
             <span v-else class="material-symbols-rounded text-[20px]">task_alt</span>
             {{ store.submitting ? 'ĐANG XỬ LÝ...' : 'XÁC NHẬN GÁN NHANH' }}
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();

const form = reactive({
  shift_type_id: null,
  override_existing: false
});

const formatDateRange = computed(() => {
  return `${store.selectedDateRange.from} đến ${store.selectedDateRange.to}`;
});

const close = () => store.closeModal('bulkAssign');

const handleApply = async () => {
  try {
    await store.bulkAssignShift({
      shift_type_id: form.shift_type_id,
      department_id: store.selectedDepartmentId,
      from_date: store.selectedDateRange.from,
      to_date: store.selectedDateRange.to,
      override_existing: form.override_existing
    });
    alert('Thành công! Lịch làm việc đã được cập nhật nhanh.');
    close();
  } catch (err) {
    alert('Lỗi: ' + err.message);
  }
};
</script>

<style scoped>
@keyframes slide-left {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
.animation-slide-left {
  animation: slide-left 0.4s cubic-bezier(0.2, 0, 0, 1);
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 4px;
}
</style>
