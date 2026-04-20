<template>
  <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity duration-500" @click="close"></div>
    <div class="relative w-full max-w-lg bg-[var(--sys-bg-surface)] rounded-[48px] shadow-2xl border border-[var(--sys-border-subtle)] overflow-hidden animation-pop-in">
      
      <!-- Header Area -->
      <div class="px-10 py-8 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-gradient-to-b from-[var(--sys-bg-page)]/80 to-transparent">
        <div class="flex items-center gap-5">
          <div :class="['w-14 h-14 rounded-[24px] flex items-center justify-center text-xl font-black shadow-xl border-2 transition-transform hover:scale-105 duration-500', data.employee?.gender === 'Female' ? 'bg-pink-50 text-pink-500 border-pink-100' : 'bg-blue-50 text-blue-500 border-blue-100']">
            {{ data.employee?.full_name.charAt(0) }}
          </div>
          <div class="space-y-0.5">
            <h3 class="text-xl font-black tracking-tight text-[var(--sys-text-primary)]">{{ data.employee?.full_name }}</h3>
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-black text-[var(--sys-brand-solid)] uppercase tracking-widest bg-[var(--sys-brand-soft)] px-2 py-0.5 rounded-md border border-[var(--sys-brand-border)]">
                {{ formatDate(data.date) }}
              </span>
              <span class="w-1 h-1 rounded-full bg-slate-300"></span>
              <span class="text-[9px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest">#{{ data.employee?.employee_code }}</span>
            </div>
          </div>
        </div>
        <button @click="close" class="w-12 h-12 rounded-2xl flex items-center justify-center bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all active:scale-90">
          <span class="material-symbols-rounded">close</span>
        </button>
      </div>

      <!-- Main Form Body -->
      <div class="p-10 space-y-8 custom-scrollbar max-h-[70vh] overflow-y-auto">
        
        <!-- Shift Selector Card Group -->
        <div class="space-y-4">
          <div class="flex items-center justify-between px-2">
            <label class="text-[10px] font-black uppercase tracking-[0.25em] text-[var(--sys-text-secondary)] opacity-50">Lựa chọn ca làm việc</label>
            <span v-if="form.shift_type_id" class="text-[9px] font-black text-[var(--sys-brand-solid)] uppercase bg-[var(--sys-brand-soft)] px-2 py-0.5 rounded-full">Đã chọn</span>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <button 
              v-for="s in store.shifts" 
              :key="s.shift_type_id"
              @click="form.shift_type_id = s.shift_type_id"
              :class="[
                'group relative flex flex-col items-center gap-2 p-5 rounded-[28px] border-2 transition-all duration-500',
                form.shift_type_id === s.shift_type_id 
                  ? 'bg-[var(--sys-brand-soft)] border-[var(--sys-brand-solid)] text-[var(--sys-brand-solid)] shadow-xl shadow-[var(--sys-brand-solid)]/10 scale-[1.02]' 
                  : 'bg-[var(--sys-bg-page)] border-transparent text-[var(--sys-text-primary)] hover:border-[var(--sys-border-subtle)] hover:scale-[1.01]'
              ]"
            >
              <div class="flex flex-col items-center">
                <span class="text-xs font-black uppercase tracking-widest">{{ s.shift_code }}</span>
                <span class="text-[10px] font-bold opacity-60 mt-1">{{ s.start_time?.slice(0,5) }} → {{ s.end_time?.slice(0,5) }}</span>
              </div>
              <div v-if="form.shift_type_id === s.shift_type_id" class="absolute -top-1 -right-1 w-5 h-5 bg-[var(--sys-brand-solid)] text-white rounded-full flex items-center justify-center shadow-lg animate-in zoom-in">
                <span class="material-symbols-rounded text-[14px] font-black">check</span>
              </div>
            </button>
            
            <button 
              @click="form.shift_type_id = null"
              :class="[
                'group relative flex flex-col items-center justify-center gap-2 p-5 rounded-[28px] border-2 transition-all duration-500 h-[84px]',
                form.shift_type_id === null 
                  ? 'bg-rose-50 border-rose-500 text-rose-600 shadow-xl shadow-rose-500/10 scale-[1.02]' 
                  : 'bg-[var(--sys-bg-page)] border-transparent text-[var(--sys-text-primary)] hover:border-[var(--sys-border-subtle)] hover:scale-[1.01]'
              ]"
            >
              <span class="text-xs font-black uppercase tracking-widest">Nghỉ (Off)</span>
              <div v-if="form.shift_type_id === null" class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-lg animate-in zoom-in">
                <span class="material-symbols-rounded text-[14px] font-black">check</span>
              </div>
            </button>
          </div>
        </div>

        <!-- Reason Input -->
        <div class="space-y-4">
          <label class="text-[10px] font-black uppercase tracking-[0.25em] text-[var(--sys-text-secondary)] opacity-50 px-2">Lý do điều chỉnh (Tùy chọn)</label>
          <div class="relative group">
            <textarea 
              v-model="form.reason"
              rows="3"
              placeholder="Nhập lý do thay đổi... (Ví dụ: Đổi ca, Nghỉ phép, Tăng ca...)"
              class="w-full px-6 py-5 rounded-[32px] bg-[var(--sys-bg-page)] border-2 border-transparent focus:border-[var(--sys-brand-solid)] outline-none transition-all duration-500 text-[13px] font-medium placeholder:text-slate-400 shadow-inner"
            ></textarea>
            <span class="material-symbols-rounded absolute right-5 bottom-5 text-slate-300 group-focus-within:text-[var(--sys-brand-solid)] transition-colors">edit_square</span>
          </div>
        </div>

        <!-- Logic Hint -->
        <div class="p-6 rounded-[32px] bg-[var(--sys-brand-soft)]/40 border border-[var(--sys-brand-border)] flex items-start gap-4 animate-in slide-in-from-top-2 duration-500">
           <div class="w-8 h-8 rounded-xl bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center shrink-0 border border-[var(--sys-brand-border)]">
             <span class="material-symbols-rounded text-[18px]">info</span>
           </div>
           <div class="space-y-1">
             <p class="text-[11px] font-black text-[var(--sys-brand-solid)] uppercase tracking-widest">Lưu ý nghiệp vụ</p>
             <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] leading-relaxed">
               Hệ thống sẽ ghi nhận đây là một <strong>Ghi đè (Override)</strong>. Dữ liệu này có ưu tiên cao nhất so với lịch mặc định trong các báo cáo chấm công.
             </p>
           </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="px-10 py-8 bg-[var(--sys-bg-page)]/80 border-t border-[var(--sys-border-subtle)] backdrop-blur-sm flex items-center justify-between gap-6">
        <button 
          v-if="data.currentShiftId !== undefined"
          @click="handleDelete"
          class="flex items-center gap-2 px-6 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest text-rose-500 hover:bg-rose-50 transition-all group"
        >
          <span class="material-symbols-rounded text-[18px] group-hover:rotate-12 transition-transform">delete_sweep</span>
          Xóa
        </button>
        <div v-else></div>
        
        <div class="flex items-center gap-4">
          <button @click="close" class="px-8 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">Hủy</button>
          <button 
            @click="handleSave"
            :disabled="store.submitting"
            class="px-10 py-4 rounded-2xl bg-[var(--sys-brand-solid)] text-white text-[12px] font-black uppercase tracking-widest hover:brightness-110 active:scale-95 transition-all shadow-xl shadow-[var(--sys-brand-solid)]/30 flex items-center gap-3 disabled:opacity-50 disabled:grayscale disabled:shadow-none"
          >
            <span v-if="store.submitting" class="material-symbols-rounded animate-spin text-[18px]">sync</span>
            <span v-else class="material-symbols-rounded text-[18px]">save</span>
            {{ store.submitting ? 'ĐANG LƯU...' : 'LƯU THAY ĐỔI' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const data = store.modals.overrideEdit?.data || {};

const form = reactive({
  shift_type_id: data.currentShiftId || null,
  reason: data.reason || ''
});

const close = () => store.closeModal('overrideEdit');

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit' });
};

const handleSave = async () => {
  try {
    await store.createOrUpdateOverride({
      employee_id: data.employee?.employee_id,
      work_date: data.date,
      shift_type_id: form.shift_type_id,
      reason: form.reason
    });
    close();
  } catch (err) {
    alert('Lỗi: ' + err.message);
  }
};

const handleDelete = async () => {
  if (confirm('Xác nhận xóa hoàn toàn ghi đè cho ngày này? (Sẽ quay lại lịch nền nếu có)')) {
    try {
      await store.deleteOverride(data.employee?.employee_id, data.date);
      close();
    } catch (err) {
      alert('Lỗi: ' + err.message);
    }
  }
};

onMounted(() => {
  if (store.shifts.length === 0) {
    store.fetchShiftTypes();
  }
});
</script>

<style scoped>
@keyframes pop-in {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.animation-pop-in {
  animation: pop-in 0.3s cubic-bezier(0.2, 0.8, 0.3, 1);
}
</style>
