<template>
  <div class="space-y-4">
    <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
      <div class="absolute top-0 right-0 p-3 opacity-10">
        <span class="material-symbols-outlined text-4xl">bolt</span>
      </div>
      
      <h3 class="font-black text-slate-800 mb-4 flex items-center gap-2 uppercase tracking-tighter text-sm">
        <span class="material-symbols-outlined text-indigo-600 text-[20px]">dynamic_form</span>
        Gán nhanh hàng loạt
      </h3>
      
      <div class="space-y-4" :class="{ 'opacity-50 pointer-events-none': !store.canEditBaseSchedule }">
        <div>
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">1. Chọn Ca làm việc</label>
          <Dropdown 
            v-model="bulkForm.shift_id" 
            :options="store.shiftOptions" 
            placeholder="-- Chọn ca hệ thống --"
            class="h-11 shadow-sm"
          />
        </div>

        <div>
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block ml-1">2. Áp dụng cho</label>
          <div class="grid grid-cols-1 gap-2">
            <button 
              v-for="opt in targetOptions" :key="opt.value"
              @click="bulkForm.target = opt.value"
              class="flex items-center gap-3 px-3 py-2 rounded-xl border-2 transition-all text-left group"
              :class="bulkForm.target === opt.value ? 'border-indigo-600 bg-indigo-50 text-indigo-900 shadow-md' : 'border-slate-100 bg-white text-slate-500 hover:border-slate-200'"
            >
              <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                   :class="bulkForm.target === opt.value ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-400 group-hover:bg-slate-100'">
                <span class="material-symbols-outlined text-[18px]">{{ opt.icon }}</span>
              </div>
              <div class="flex-1">
                <div class="text-[11px] font-black uppercase tracking-tight">{{ opt.label }}</div>
                <div class="text-[9px] font-bold opacity-60">{{ opt.desc }}</div>
              </div>
              <div v-if="opt.value === 'selected' && store.selectedCount > 0" 
                   class="bg-indigo-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full animate-in zoom-in">
                {{ store.selectedCount }}
              </div>
            </button>
          </div>
        </div>

        <button 
          @click="handleBulkAssign"
          :disabled="!isReady || store.submitting || !store.canEditBaseSchedule"
          class="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-black text-sm shadow-xl shadow-slate-200 transition-all active:scale-95 disabled:opacity-30 flex items-center justify-center gap-2 group mt-2"
        >
          <span class="material-symbols-outlined group-hover:rotate-12 transition-transform">rocket_launch</span>
          ÁP DỤNG NGAY
        </button>
      </div>

      <div v-if="!store.canEditBaseSchedule" class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl flex items-start gap-3">
        <span class="material-symbols-outlined text-amber-600 text-[20px]">lock_person</span>
        <p class="text-[10px] text-amber-800 font-bold leading-relaxed uppercase italic">Lịch nền đã được công bố & khóa. Vui lòng sử dụng Ghi đè (Override).</p>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
      <h3 class="font-black text-slate-800 mb-3 flex items-center gap-2 uppercase tracking-tighter text-sm opacity-60">
        <span class="material-symbols-outlined text-[20px]">auto_stories</span>
        Tiện ích bổ trợ
      </h3>
      <button 
        @click="handleCopyWeek"
        :disabled="!store.canEditBaseSchedule || store.submitting"
        class="w-full py-3 border-2 border-slate-50 hover:border-indigo-100 hover:bg-indigo-50/50 text-slate-600 hover:text-indigo-600 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 disabled:opacity-50"
      >
        <span class="material-symbols-outlined text-[18px]">history</span>
        Sao chép từ tuần trước
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';
import Dropdown from '@/components/Dropdown.vue';

const store = useScheduleStore();
const bulkForm = ref({
  shift_id: null,
  target: 'selected'
});

const targetOptions = [
  { label: 'Nhân sự đã chọn', value: 'selected', desc: 'Chỉ áp dụng cho các dòng đã tích checkbox', icon: 'checklist' },
  { label: 'Tất cả nhân sự', value: 'all', desc: 'Áp dụng cho toàn bộ danh sách hiển thị', icon: 'groups' },
  { label: 'Chưa có ca', value: 'unassigned', desc: 'Chỉ áp dụng cho ai còn trống lịch', icon: 'person_search' },
];

const isReady = computed(() => {
  if (!bulkForm.value.shift_id) return false;
  if (bulkForm.value.target === 'selected' && store.selectedCount === 0) return false;
  return true;
});

const handleBulkAssign = async () => {
  try {
     const shift = store.shifts.find(s => (s.shift_type_id || s.id) === bulkForm.value.shift_id);
     store.openModal('confirmAction', {
        type: 'bulk',
        title: 'Xác nhận gán nhanh',
        message: `Bạn đang chuẩn bị gán ca "${shift.shift_code || shift.code}" cho các đối tượng đã chọn. Dữ liệu cũ sẽ bị ghi đè. Bạn có chắc chắn?`,
        shift_type_id: bulkForm.value.shift_id,
        target: bulkForm.value.target
     });
  } catch (err) {
    alert(err.message);
  }
};

const handleCopyWeek = () => {
  store.previewSuggestion('copy_week');
};
</script>
